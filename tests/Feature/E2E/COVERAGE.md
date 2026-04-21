# Relatório de cobertura — Suíte E2E (Cup Share API)

Execução final: **48 testes E2E passando / 262 assertions** (suíte Feature completa: 142 passando / 472 assertions).

Comando: `./vendor/bin/sail artisan test --filter=E2E`

---

## Mapeamento fluxo → arquivo de teste

| # | Fluxo solicitado | Arquivo | Status |
|---|---|---|---|
| 1 | Onboarding completo | [OnboardingFlowTest.php](OnboardingFlowTest.php) | Coberto |
| 2 | Ciclo de vida de receita (private → public → like → delete) | [RecipeLifecycleFlowTest.php](RecipeLifecycleFlowTest.php) | Coberto |
| 3 | Equipamentos globais vs. pessoais | [EquipmentAttachmentFlowTest.php](EquipmentAttachmentFlowTest.php) | **Coberto parcialmente** (ver divergências) |
| 4 | Regras de autorização (401/403) | [AuthorizationFlowTest.php](AuthorizationFlowTest.php) | Coberto |
| 5 | Ban flow | [BanFlowTest.php](BanFlowTest.php) | Coberto |
| 6 | Magic link edge cases | [MagicLinkEdgeCasesFlowTest.php](MagicLinkEdgeCasesFlowTest.php) | Coberto |
| 7 | Receita de filtro vs. espresso (ratio) | [RecipeLifecycleFlowTest.php](RecipeLifecycleFlowTest.php) | Coberto |
| 8 | Likes counter cache via API | [LikesCounterFlowTest.php](LikesCounterFlowTest.php) | Coberto |
| 9 | Soft deletes | [SoftDeleteFlowTest.php](SoftDeleteFlowTest.php) | Coberto |
| 10 | Paginação e filtros | [FeedFilterPaginationFlowTest.php](FeedFilterPaginationFlowTest.php) | Coberto |

## Detalhes por arquivo

### OnboardingFlowTest
- `test_new_user_onboards_via_magic_link_and_creates_first_recipe` — fluxo real ponta-a-ponta: POST `/auth/magic-link` → GET `/auth/magic-link/{token}` → Bearer → GET `/users/me` → PUT `/users/me` → POST `/recipes` → GET `/recipes` (feed anônimo).
- `test_onboarding_marks_email_as_verified_on_first_token_consumption`.

### RecipeLifecycleFlowTest
- `test_private_recipe_becomes_public_receives_like_and_is_deleted` — jornada A↔B completa com assertSoftDeleted.
- `test_filter_recipe_has_ratio_calculated_from_water_ml` — valida ratio `1:16.7` para 15g/250ml.
- `test_espresso_recipe_has_ratio_calculated_from_yield_ml` — valida ratio `1:2` para 18g/36ml.
- `test_recipe_update_preserves_steps_when_steps_not_provided` — valida comportamento do `syncSteps` quando `steps` não está no payload.

### EquipmentAttachmentFlowTest
- `test_admin_creates_global_equipment_and_user_attaches_to_recipe` — fluxo completo admin → user → recipe.
- `test_user_can_attach_equipment_via_custom_name_without_equipment_id` — regra XOR.
- `test_attaching_equipment_requires_either_equipment_id_or_custom_name`.
- `test_non_admin_cannot_create_global_equipment` (403).
- `test_non_owner_cannot_attach_equipment_to_others_recipe` (403).

### AuthorizationFlowTest
- `test_guest_receives_401_on_protected_user_routes`.
- `test_guest_receives_401_on_protected_recipe_routes`.
- `test_user_receives_403_editing_anothers_recipe` (update, delete, visibility, equipment).
- `test_regular_user_receives_403_on_admin_routes`.
- `test_guest_receives_401_on_admin_routes`.
- `test_admin_can_access_admin_routes`.

### BanFlowTest
- `test_admin_bans_user_and_banned_user_receives_403_with_ban_payload` — valida shape `{message, banned_at, ban_reason}`.
- `test_banned_user_regains_access_after_unban`.
- `test_banned_user_cannot_create_recipe_or_like`.
- `test_ban_response_includes_banned_at_timestamp`.
- `test_public_routes_remain_accessible_to_banned_users` — confirma que middleware `not_banned` só roda em rotas protegidas.

### MagicLinkEdgeCasesFlowTest
- `test_expired_magic_link_is_rejected_after_15_minutes` — usa `Carbon::setTestNow`.
- `test_valid_magic_link_still_works_before_expiration` (14min).
- `test_consumed_magic_link_cannot_be_reused`.
- `test_invalid_token_returns_422`.
- `test_new_magic_link_request_invalidates_previous_pending_tokens`.
- `test_logout_revokes_sanctum_token_from_magic_link` — fluxo Bearer real + `refreshApplication`.
- `test_magic_link_request_invalid_email_returns_422`.
- `test_magic_link_always_returns_202_for_security_even_if_email_new`.

### LikesCounterFlowTest
- `test_multiple_users_likes_increment_counter_via_api` (3 usuários → `likes_count=3`).
- `test_unlike_decrements_counter_via_api`.
- `test_counter_is_consistent_after_like_unlike_like_sequence`.
- `test_user_cannot_like_own_recipe` (403).
- `test_user_cannot_like_same_recipe_twice` (422).
- `test_liked_by_me_reflects_auth_users_like_state`.
- `test_likes_on_private_recipe_return_404`.

### SoftDeleteFlowTest
- `test_deleted_recipe_is_removed_from_public_feed_and_returns_404` + `assertSoftDeleted`.
- `test_deleted_user_account_is_soft_deleted`.
- `test_deleted_user_cannot_reuse_previous_bearer_token`.
- `test_admin_deleted_equipment_disappears_from_public_listing` + `assertSoftDeleted('equipment')`.
- `test_deleted_recipe_keeps_likes_records_but_is_inaccessible`.

### FeedFilterPaginationFlowTest
- `test_public_feed_is_paginated_with_15_per_page` (20 receitas → 2 páginas).
- `test_feed_filters_by_brew_method_id`.
- `test_feed_filters_by_category`.
- `test_feed_hides_private_recipes_from_anonymous_and_from_other_users`.
- `test_my_recipes_endpoint_returns_private_and_public_for_owner`.
- `test_feed_sorts_by_likes_count_desc`.

---

## Divergências entre o prompt e o código atual

### 1. Equipamentos pessoais não existem mais

Migration `2026_03_22_100013_remove_user_id_from_equipment_table.php` removeu a coluna `user_id` de `equipment`. Hoje **todos os equipamentos são globais** — só podem ser criados/editados/deletados por admins via `/api/v1/admin/equipment`.

O fluxo #3 solicitado pelo prompt ("usuário cria equipamento pessoal → outro usuário NÃO o vê") não tem suporte no schema atual. Foi adaptado para testar apenas o cenário global (admin cria, usuário vincula à receita).

Usuários ainda podem criar equipamentos "customizados" por receita via `custom_name` na pivot `recipe_equipment`, mas não há conceito de equipamento de usuário reutilizável.

**Recomendação de atualização de CLAUDE.md**: a seção "Equipamentos" (linhas 93-97) fala de equipamento "Pessoal: user_id = X" que não corresponde à realidade. Sugere-se remover ou reescrever.

### 2. Ingredients foram removidos

Migration `2026_03_22_100012_remove_ingredients_tables.php` removeu as tabelas `ingredients` e `recipe_ingredient`. Não há controller, resource, Form Request nem rotas para ingredients.

O prompt menciona "steps e ingredients" no fluxo #2 e "Ingredients: sincronizados via sync()" em CLAUDE.md — ambos desatualizados. Testes cobrem apenas `steps`.

**Recomendação de atualização de CLAUDE.md**: linhas 99-108 (seção "Receitas") mencionam ingredients, pivot `recipe_ingredient`, sync via `sync()`. Remover.

### 3. `recipe_type_id` também foi removido

Migration `2026_03_22_100011_remove_recipe_type_from_recipes_table.php` removeu o FK para `recipe_types`. CLAUDE.md não menciona recipe_types explicitamente, mas os seeders antigos ainda existem em `database/seeders/`. Esses seeders ficaram órfãos (não referenciados em DatabaseSeeder provavelmente).

---

## Bugs e inconsistências detectados durante a execução

### Comportamento a observar (não são bugs, mas vale documentar)

1. **Route model binding ocorre ANTES do middleware `not_banned`**. Se um usuário banido acessar uma rota com `{recipe}` inexistente (ex: `POST /recipes/999/likes`), recebe **404** em vez do 403 esperado do ban. Isso foi verificado empiricamente: `SubstituteBindings` (middleware `api`) roda antes dos middlewares de rota.
   - Impacto: o frontend não receberá o payload de ban suspense se tentar interagir com recursos inexistentes. Pode ser desejável ou não.
   - Teste que documenta: `BanFlowTest::test_banned_user_cannot_create_recipe_or_like` (força a criação de um recipe válido antes de testar).

2. **`refreshApplication()` + `RefreshDatabase`**: chamar `refreshApplication()` no meio de um teste aparenta quebrar a transaction do `RefreshDatabase`, fazendo com que `assertSoftDeleted` depois do refresh relate "The table is empty". O teste `MagicLinkTest::test_logout_revokes_token` funciona porque não faz asserções de DB após o refresh — apenas revalidação de token. Para evitar confusão, a suíte E2E separou em dois testes: um para o soft delete, outro para a revogação do token (ver `SoftDeleteFlowTest`).

3. **`UserResource` expõe `is_admin` e `banned_at` publicamente para o próprio usuário**. Ao chamar `GET /users/me`, o campo `is_admin` vem sempre, mesmo para usuários regulares (`false`). Não é bug, mas é uma escolha de design que merece consideração: o frontend pode usá-lo para habilitar áreas admin sem chamada extra, mas também expõe implementação. Manter como está é razoável.

4. **`RecipeResource.ratio`** é uma string formatada `1:X.Y` (ex: `"1:16.7"`, `"1:2"`). Para espresso 18g/36ml, retorna `"1:2"` (não `"1:2.0"`) porque `round(36/18, 1) = 2` (inteiro). Testes E2E assertam `"1:2"` explicitamente para cobrir esse caso.

5. **`MagicLinkController::destroy` (logout)** está atrás do middleware `not_banned`. Isso significa que um usuário banido **não consegue fazer logout** — recebe 403. O teste `BanFlowTest::test_banned_user_cannot_create_recipe_or_like` documenta esse comportamento. Pode ser intencional, mas vale consideração: um usuário banido provavelmente **deveria** conseguir se deslogar.

### Sugestões de melhoria (sem alterações no código de produção)

1. **Atualizar CLAUDE.md** para refletir schema atual (remover referências a ingredients, equipamento pessoal, recipe_type_id).
2. **Remover seeders órfãos** (`RecipeSeeder`? — não foi validado neste relatório; recomenda-se auditoria em `database/seeders/`).
3. **Considerar mover `not_banned` para depois do `/auth/logout`** ou liberar logout para usuários banidos.
4. **Considerar reordenar middleware**: aplicar `not_banned` antes de `SubstituteBindings` para que usuários banidos sempre recebam 403 consistente, mesmo em recursos inexistentes. Isso exigiria configuração customizada de `$middlewarePriority` em `bootstrap/app.php`.
5. **Documentação de status codes**: o controller `BrewMethodController::store` retorna 201 implicitamente via `RestResource`, mas o `EquipmentController::store` força `setStatusCode(201)`. Inconsistência menor — padronizar.

---

## Cenários intencionalmente não cobertos

- **`admin` criando brew-method com endpoint já existe** — coberto em `AdminBrewMethodTest.php` (fora do escopo E2E).
- **Validações exaustivas de Form Requests** — cobertas em `RecipeCrudTest.php`, `RecipeFilterTest.php` (fora do escopo E2E).
- **Lista admin de magic-links** — `AdminMagicLinkTest.php` já cobre. E2E não duplicou.

---

## Como rodar

```bash
# Apenas E2E
./vendor/bin/sail artisan test --filter=E2E

# Por arquivo
./vendor/bin/sail artisan test --filter=OnboardingFlowTest
./vendor/bin/sail artisan test --filter=RecipeLifecycleFlowTest
./vendor/bin/sail artisan test --filter=EquipmentAttachmentFlowTest
./vendor/bin/sail artisan test --filter=AuthorizationFlowTest
./vendor/bin/sail artisan test --filter=BanFlowTest
./vendor/bin/sail artisan test --filter=MagicLinkEdgeCasesFlowTest
./vendor/bin/sail artisan test --filter=LikesCounterFlowTest
./vendor/bin/sail artisan test --filter=SoftDeleteFlowTest
./vendor/bin/sail artisan test --filter=FeedFilterPaginationFlowTest

# Suíte Feature completa
./vendor/bin/sail artisan test --testsuite=Feature
```
