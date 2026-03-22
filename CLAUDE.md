# CLAUDE.md — Cup Share API

Guia de contexto para o Claude Code trabalhar neste projeto.

---

## Ambiente e comandos

**SEMPRE** rodar comandos PHP/Laravel via Laravel Sail, nunca diretamente no host:

```bash
# Correto
./vendor/bin/sail artisan <comando>
./vendor/bin/sail composer <comando>
./vendor/bin/sail php <arquivo>

# Errado
php artisan <comando>
composer <comando>
```

### Comandos essenciais

```bash
./vendor/bin/sail up -d                        # Sobe os containers
./vendor/bin/sail down                         # Para os containers
./vendor/bin/sail artisan migrate              # Roda migrations
./vendor/bin/sail artisan migrate:fresh --seed # Recria banco com seeders
./vendor/bin/sail artisan test                 # Roda os testes
./vendor/bin/sail artisan queue:work           # Processa fila de e-mails
./vendor/bin/sail artisan tinker               # REPL interativo
./vendor/bin/sail artisan route:list           # Lista todas as rotas
./vendor/bin/sail artisan make:model Foo -mf   # Model + migration + factory
```

---

## Stack

- **PHP 8.5** + **Laravel 13**
- **PostgreSQL 17** (host Sail: `pgsql`, banco: `cup_share`, teste: `testing`)
- **Redis** — cache e filas
- **Laravel Sanctum** — autenticação via Bearer token
- **dedoc/scramble** — documentação OpenAPI em `/docs/api` (somente `local`)
- **Mailpit** — captura de e-mails em `http://localhost:8025`

---

## Estrutura de pastas relevante

```
app/
  Enums/           # RecipeVisibility, EquipmentType (PHP 8.x backed enums)
  Http/
    Controllers/   # Um controller por recurso; Auth/ para magic link
    Requests/      # Form Requests para validação (um por operação)
    Resources/     # API Resources para transformação JSON
  Models/          # Eloquent models
  Notifications/   # MagicLinkNotification (ShouldQueue)
  Policies/        # RecipePolicy, EquipmentPolicy
  Services/        # MagicLinkService
database/
  factories/       # Para testes
  migrations/      # Prefixo 2026_03_22_1000XX_*
  seeders/         # BrewMethod, RecipeType, Ingredient, Equipment
routes/
  api.php          # Todas as rotas; prefixo api/v1 configurado em bootstrap/app.php
tests/
  Feature/
    Auth/
    Equipment/
    Recipe/
```

---

## Convenções do projeto

### Controllers
- Um controller por recurso, seguindo o padrão Resource do Laravel
- Usar `authorize()` via trait `AuthorizesRequests` (já incluído no `Controller` base)
- Retornos: `201` para criação, `204` para deleção, `202` para operações assíncronas
- Operações que alteram múltiplas tabelas (ex: recipe + steps + ingredients) devem usar `DB::transaction()`

### Models
- Soft Deletes em: `User`, `Recipe`, `Equipment`
- `MagicLink`, `RecipeStep`, `Like` — sem timestamps padrão (`public $timestamps = false`)
- Pivot tables com nome explícito sempre que divergir do padrão Eloquent:
  - `recipe_ingredient` (não `ingredient_recipe`)
  - `recipe_equipment`
- Enums como casts: `RecipeVisibility` em `Recipe`, `EquipmentType` em `Equipment`
- Counter cache: `likes_count` em `Recipe` — incrementar/decrementar via `increment()`/`decrement()`, nunca recalcular com `count()`

### Equipamentos
- **Global**: `user_id IS NULL` — visível a todos, não pode ser deletado por usuários
- **Pessoal**: `user_id = X` — visível apenas ao dono, gerenciável pelo dono
- Scopes: `scopeGlobal()`, `scopeForUser($userId)`, `scopeGrinders()`

### Receitas
- `water_ml` — usado em receitas de filtro (V60, Aeropress etc.)
- `yield_ml` — usado em receitas de espresso (volume extraído)
- Ambos são nullable e mutuamente exclusivos por convenção de tipo de receita
- Ratio calculado dinamicamente no Resource: `coffee_grams / (water_ml ?? yield_ml)`
- Visibilidade: `public` | `private` (enum `RecipeVisibility`)
- Steps: gerenciados inline no payload da receita — delete + createMany em transação
- Ingredients: sincronizados via `sync()` com pivot `quantity` e `unit`

### Autenticação
- Passwordless via Magic Link — token de 64 chars, expira em 15min (`.env`: `MAGIC_LINK_EXPIRES_MINUTES`)
- Tokens anteriores inválidos são deletados ao solicitar novo link
- Após consumo do token, Sanctum emite Bearer token via `createToken('magic-link')`
- `statefulApi()` **não** está ativo — API é puramente stateless por token

### Testes
- Trait `RefreshDatabase` em todos os Feature tests
- Banco de testes: PostgreSQL (host `pgsql`, banco `testing`) — configurado em `phpunit.xml`
- Fila em `sync` nos testes (configurado em `phpunit.xml`)
- Usar `actingAs($user, 'sanctum')` para autenticar em testes de rotas protegidas
- Factories disponíveis: `User`, `Recipe`, `BrewMethod`, `RecipeType`

---

## O que NÃO fazer

- Não usar `php artisan` ou `composer` diretamente (sempre via Sail)
- Não adicionar `statefulApi()` ao `bootstrap/app.php` — quebra testes de logout
- Não usar `email:rfc,dns` em validações — DNS lookup falha em ambiente de teste
- Não recalcular `likes_count` com query — usar `increment`/`decrement`
- Não omitir o nome explícito da pivot table `recipe_ingredient` nas relações Eloquent
- Não criar rotas web — este projeto é API-only
- Não expor a documentação Scramble fora do ambiente `local`
