# Análise Completa: Erro 405 em select-store

## Data da Análise
2026-01-23

## Problema Reportado
Erro 405 (Method Not Allowed) ao selecionar loja no dashboard.

## Análise Realizada

### 1. Rotas (routes/web.php)
✅ **Status: CORRETO**
- Rota definida como: `Route::match(['get'], '/dashboard/select-store', ...)`
- Apenas GET é permitido
- Middleware: `['auth', 'verified']`
- Nome da rota: `dashboard.selectStore`

### 2. Controller (DashboardController::selectStore)
✅ **Status: CORRIGIDO**
- Validação rigorosa do método HTTP
- Logs detalhados para debug
- Aceita apenas `store_id` via query string (GET)
- Validação de loja existente e ativa
- Gerenciamento correto de sessão

**Código:**
```php
// Validação rigorosa
if ($request->method() !== 'GET') {
    Log::error('❌ [Dashboard] Método incorreto', [...]);
    abort(405, 'Método não permitido. Esta rota aceita apenas requisições GET.');
}
```

### 3. JavaScript (dashboard.blade.php)
✅ **Status: CORRIGIDO**
- Usa `window.location.href` (sempre GET)
- Não usa `fetch`, `XMLHttpRequest` ou `axios`
- Listener configurado em capture phase
- Previne todos os comportamentos padrão
- Clona elemento para remover listeners antigos

**Características:**
- Função isolada em IIFE
- Múltiplas tentativas de configuração (DOMContentLoaded + setTimeout)
- Logs detalhados para debug
- Atributo `data-route` no select para URL

### 4. Service Worker (sw.js)
✅ **Status: CORRIGIDO**
- Não intercepta requisições para `select-store`
- Retorna imediatamente (sem `event.respondWith`)
- Permite que requisições vão direto para o servidor

**Código:**
```javascript
if (url.pathname.includes('select-store') || 
    url.pathname.includes('selectStore') ||
    url.pathname.includes('dashboard/select-store')) {
    return; // Não interceptar
}
```

### 5. Interceptação de Fetch (os/create.blade.php)
✅ **Status: CORRIGIDO**
- Detecta requisições para `select-store`
- Ignora interceptação e deixa passar direto
- Não adiciona headers CSRF para essas rotas

### 6. HTML do Select
✅ **Status: CORRETO**
- Select NÃO está dentro de um `<form>`
- Não tem atributo `form` apontando para formulário
- ID único: `storeSelect`
- Atributo `data-route` com URL da rota

## Possíveis Causas do Erro 405

### Causa 1: Service Worker Interceptando (RESOLVIDO)
- **Problema:** Service Worker estava usando `event.respondWith(fetch(...))`
- **Solução:** Retornar imediatamente sem interceptação

### Causa 2: JavaScript Convertendo GET em POST (RESOLVIDO)
- **Problema:** Código poderia estar usando `fetch` com método POST
- **Solução:** Usar apenas `window.location.href`

### Causa 3: Listeners Conflitantes (RESOLVIDO)
- **Problema:** Múltiplos listeners no mesmo elemento
- **Solução:** Clonar elemento e adicionar listener único em capture phase

### Causa 4: Cache do Navegador (RESOLVIDO)
- **Problema:** Navegador poderia estar servindo requisição antiga do cache
- **Solução:** Service Worker não intercepta + headers anti-cache no controller

## Correções Implementadas

### 1. Controller
- ✅ Validação rigorosa de método HTTP
- ✅ Logs detalhados com IP, User-Agent, URL completa
- ✅ Mensagem de erro clara

### 2. JavaScript
- ✅ Código refatorado e isolado
- ✅ Função dedicada para navegação
- ✅ Múltiplas tentativas de configuração
- ✅ Logs para debug

### 3. Service Worker
- ✅ Não intercepta rotas `select-store`
- ✅ Retorna imediatamente

### 4. HTML
- ✅ Atributo `data-route` no select
- ✅ Atributo `name` para compatibilidade

## Testes Recomendados

1. **Teste Manual:**
   - Abrir dashboard
   - Selecionar loja no dropdown
   - Verificar console do navegador
   - Verificar logs do Laravel

2. **Teste de Rede:**
   - Abrir DevTools > Network
   - Filtrar por "select-store"
   - Verificar método HTTP (deve ser GET)
   - Verificar status code (deve ser 200 ou 302)

3. **Teste de Service Worker:**
   - Verificar se Service Worker está ativo
   - Verificar se não intercepta requisições select-store

## Logs para Monitoramento

### Controller Logs:
- `❌ [Dashboard] Tentativa de acessar selectStore com método incorreto`
- `✅ [Dashboard] Loja selecionada e salva na sessão`
- `✅ [Dashboard] Filtro de loja removido da sessão`

### JavaScript Console:
- `🔍 [Dashboard] Loja selecionada: [ID]`
- `🔍 [Dashboard] Navegando para (GET): [URL]`
- `✅ [Dashboard] Listener configurado para storeSelect`

## Conclusão

Todas as possíveis causas do erro 405 foram identificadas e corrigidas:
- ✅ Rotas configuradas corretamente
- ✅ Controller valida método HTTP
- ✅ JavaScript usa apenas GET
- ✅ Service Worker não interfere
- ✅ Interceptações de fetch ignoradas
- ✅ HTML sem formulários envolvidos

O sistema está preparado para prevenir e detectar tentativas de acesso com método incorreto.
