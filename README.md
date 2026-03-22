# Cup Share API

API RESTful para a plataforma **Cup Share** — compartilhe e descubra receitas de café. Desenvolvida com Laravel 13, PHP 8.5, PostgreSQL 17 e autenticação passwordless via Magic Link.

---

## Stack

| Tecnologia | Versão | Uso |
|---|---|---|
| PHP | 8.5 | Runtime |
| Laravel | 13 | Framework |
| PostgreSQL | 17 | Banco de dados |
| Redis | Alpine | Cache e filas |
| Laravel Sanctum | — | Autenticação via Bearer token |
| Laravel Sail | — | Ambiente Docker local |
| Mailpit | — | Captura de e-mails em desenvolvimento |
| dedoc/scramble | — | Documentação OpenAPI automática |

---

## Pré-requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (ou Docker Engine + Compose)
- PHP 8.3+ instalado localmente (apenas para rodar o `composer install` inicial)
- Composer

---

## Instalação

### 1. Clone o repositório e instale as dependências PHP

```bash
git clone <repo-url> cup-share-api
cd cup-share-api
composer install
```

### 2. Configure o ambiente

```bash
cp .env.example .env
./vendor/bin/sail artisan key:generate
```

### 3. Suba os containers

```bash
./vendor/bin/sail up -d
```

Isso inicializa os serviços: **Laravel** (porta 80), **PostgreSQL** (5432), **Redis** (6379) e **Mailpit** (8025).

### 4. Execute as migrations e seeders

```bash
./vendor/bin/sail artisan migrate --seed
```

Os seeders populam o banco com dados iniciais:
- **Brew Methods**: V60, Aeropress, Chemex, Moka Pot, French Press, Kalita Wave, Espresso, entre outros
- **Recipe Types**: Filtrado, Espresso, Cold Brew, Latte, Cappuccino, entre outros
- **Ingredients**: Leite integral, Leite vegetal, Açúcar, Gelo, entre outros
- **Equipment**: La Marzocco Linea Mini, Comandante C40, Acaia Pearl, Hario V60, entre outros

---

## Serviços locais

| Serviço | URL | Descrição |
|---|---|---|
| API | `http://localhost/api/v1` | Base URL de todos os endpoints |
| Documentação | `http://localhost/docs/api` | OpenAPI interativo (Scramble) |
| Mailpit | `http://localhost:8025` | Visualizar e-mails enviados |

---

## Autenticação

A API usa autenticação **passwordless via Magic Link**. Não existe cadastro separado — a conta é criada automaticamente no primeiro acesso.

### Fluxo

**1. Solicitar o link**

```http
POST /api/v1/auth/magic-link
Content-Type: application/json

{
  "email": "voce@exemplo.com"
}
```

A API sempre retorna `202` independente do e-mail existir ou não (segurança contra enumeração de usuários). Se for o primeiro acesso, a conta é criada automaticamente.

**2. Verificar o e-mail**

Em desenvolvimento, acesse o **Mailpit** em `http://localhost:8025` para ver o e-mail com o link.

**3. Consumir o token**

```http
GET /api/v1/auth/magic-link/{token}
```

Retorna um Bearer token do Sanctum:

```json
{
  "token": "1|abc123..."
}
```

**4. Usar o token nas requisições**

```http
Authorization: Bearer 1|abc123...
```

O token expira em **15 minutos** se não for consumido. Após o consumo, é válido até o logout.

### Logout

```http
DELETE /api/v1/auth/logout
Authorization: Bearer {token}
```

---

## Filas (Queue)

Os e-mails são enviados via fila. Em desenvolvimento você tem duas opções:

**Opção A — Rodar o worker** (recomendado para simular produção):

```bash
./vendor/bin/sail artisan queue:work
```

**Opção B — Processar na hora** (sem precisar de worker):

No `.env`, altere:
```dotenv
QUEUE_CONNECTION=sync
```

---

## Endpoints

### Públicos (sem autenticação)

| Método | Endpoint | Descrição |
|---|---|---|
| `POST` | `/auth/magic-link` | Solicita magic link |
| `GET` | `/auth/magic-link/{token}` | Consome token e retorna Bearer |
| `GET` | `/brew-methods` | Lista métodos de preparo |
| `GET` | `/brew-methods/{id}` | Detalhe de um método |
| `GET` | `/recipe-types` | Lista tipos de receita |
| `GET` | `/recipe-types/{id}` | Detalhe de um tipo |
| `GET` | `/ingredients` | Lista ingredientes extras |
| `GET` | `/ingredients/{id}` | Detalhe de um ingrediente |
| `GET` | `/equipment` | Lista equipamentos globais |
| `GET` | `/equipment/{id}` | Detalhe de um equipamento |
| `GET` | `/recipes` | Feed de receitas públicas (paginado) |
| `GET` | `/recipes/{id}` | Detalhe de uma receita pública |
| `GET` | `/recipes/{id}/likes` | Contagem e status de likes |

### Protegidos (requer `Authorization: Bearer {token}`)

| Método | Endpoint | Descrição |
|---|---|---|
| `DELETE` | `/auth/logout` | Revoga o token atual |
| `GET` | `/users/me` | Perfil do usuário autenticado |
| `PUT` | `/users/me` | Atualiza nome do usuário |
| `DELETE` | `/users/me` | Remove a conta (soft delete) |
| `GET` | `/users/me/recipes` | Minhas receitas (públicas + privadas) |
| `GET` | `/users/me/equipment` | Meus equipamentos personalizados |
| `POST` | `/recipes` | Cria receita |
| `PUT` | `/recipes/{id}` | Atualiza receita (somente dono) |
| `DELETE` | `/recipes/{id}` | Remove receita (somente dono) |
| `PATCH` | `/recipes/{id}/visibility` | Alterna visibilidade público/privado |
| `POST` | `/recipes/{id}/likes` | Dá like em uma receita |
| `DELETE` | `/recipes/{id}/likes` | Remove like |
| `POST` | `/recipes/{id}/equipment` | Vincula equipamento à receita |
| `PUT` | `/recipes/{id}/equipment/{equipmentId}` | Atualiza parâmetros do equipamento |
| `DELETE` | `/recipes/{id}/equipment/{equipmentId}` | Desvincula equipamento |
| `POST` | `/brew-methods` | Cria método de preparo |
| `PUT` | `/brew-methods/{id}` | Atualiza método |
| `DELETE` | `/brew-methods/{id}` | Remove método |
| `POST` | `/recipe-types` | Cria tipo de receita |
| `PUT` | `/recipe-types/{id}` | Atualiza tipo |
| `DELETE` | `/recipe-types/{id}` | Remove tipo |
| `POST` | `/ingredients` | Cria ingrediente |
| `PUT` | `/ingredients/{id}` | Atualiza ingrediente |
| `DELETE` | `/ingredients/{id}` | Remove ingrediente |
| `POST` | `/equipment` | Cria equipamento (global ou pessoal) |
| `PUT` | `/equipment/{id}` | Atualiza equipamento |
| `DELETE` | `/equipment/{id}` | Remove equipamento pessoal |

> A documentação interativa completa com schemas de request/response está disponível em `http://localhost/docs/api`.

---

## Exemplo: Criar uma receita

```http
POST /api/v1/recipes
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "V60 Clarity",
  "brew_method_id": 1,
  "recipe_type_id": 1,
  "coffee_grams": 15,
  "water_ml": 250,
  "brew_time_seconds": 180,
  "visibility": "public",
  "description": "Receita clean e brilhante para o V60.",
  "steps": [
    { "order": 1, "description": "Aqueça a água a 94°C.", "duration_seconds": null },
    { "order": 2, "description": "Bloom: despeje 30ml e aguarde 30s.", "duration_seconds": 30 },
    { "order": 3, "description": "Despeje o restante em espiral até 250ml.", "duration_seconds": 150 }
  ],
  "ingredients": []
}
```

Para receitas de **espresso**, use `yield_ml` no lugar de `water_ml`:

```json
{
  "title": "Espresso Clássico",
  "water_ml": null,
  "yield_ml": 36,
  "coffee_grams": 18,
  ...
}
```

---

## Testes

```bash
# Rodar todos os testes
./vendor/bin/sail artisan test

# Com cobertura detalhada
./vendor/bin/sail artisan test --coverage
```

A suíte cobre autenticação (magic link), CRUD de receitas, visibilidade, likes e equipamentos.

---

## Comandos úteis

```bash
# Parar os containers
./vendor/bin/sail down

# Recriar o banco do zero
./vendor/bin/sail artisan migrate:fresh --seed

# Acessar o PostgreSQL via CLI
./vendor/bin/sail psql

# Ver logs da aplicação
./vendor/bin/sail logs -f laravel.test

# Rodar tinker (REPL)
./vendor/bin/sail artisan tinker
```
