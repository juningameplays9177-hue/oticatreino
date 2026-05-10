# Instruções para Importação de Produtos ZEISS

## Comando Criado

Foi criado o comando `products:import-zeiss-catalog` que importa produtos do catálogo ZEISS para o banco de dados.

## Como Usar

### 1. Testar primeiro (modo dry-run)

Execute o comando em modo de teste para ver quais produtos serão importados:

```bash
php artisan products:import-zeiss-catalog --dry-run
```

Este comando irá:
- Extrair produtos do catálogo ZEISS
- Exibir os primeiros 30 produtos encontrados
- **NÃO salvará nada no banco de dados**

### 2. Importar para o banco

Após verificar que os produtos estão corretos:

```bash
php artisan products:import-zeiss-catalog
```

Ou especificar um fornecedor diferente:

```bash
php artisan products:import-zeiss-catalog --supplier="Nome do Fornecedor"
```

## Produtos Incluídos

O comando importa os seguintes produtos do catálogo ZEISS:

### Lentes Visão Simples:
- ZEISS SmartLife Individual 3 (todos os índices e tratamentos)
- ZEISS SmartLife (todos os índices e tratamentos)
- ZEISS Light 2 (todos os índices e tratamentos)
- ZEISS SmartLife Young
- ZEISS MyoCare e MyoCare S
- ZEISS ClearView
- ZEISS ClassicPlus
- ZEISS SmartLife Digital Individual 3
- ZEISS Individual DriveSafe
- ZEISS Individual Sport
- ZEISS EnergizeMe

### Lentes Multifocais:
- ZEISS Progressive SmartLife Individual 3
- ZEISS Progressive SmartLife Superb
- ZEISS Progressive SmartLife Plus
- ZEISS Progressive SmartLife Pure
- ZEISS Progressive SmartLife Essential
- ZEISS Progressive Light 2 3Dv
- ZEISS Progressive Light 2 3D
- ZEISS Progressive Light 2 D
- ZEISS Progressive GT2
- ZEISS Progressive ClassicPlus
- ZEISS OfficeLens Individual
- ZEISS Progressive Individual DriveSafe
- ZEISS Progressive Individual Sport

### Tratamentos e Serviços:
- Colorações FARB ZEISS
- Tratamentos DuraVision (Gold UV, Platinum UV, Silver UV, Chrome UV, DriveSafe)
- SKYLET
- Filtros Medicinais

## Índices de Refração Suportados

- 1.50 (Resina)
- Poli (1.59)
- 1.60
- 1.67
- 1.74

## Tratamentos Incluídos

- BlueGuard
- DuraVision Gold UV
- DuraVision Platinum UV
- DuraVision Silver UV
- DuraVision Chrome UV
- PhotoFusion X (Cinza e Extra Dark Cinza)
- Polarizada (Verde/Cinza/Marrom)
- DuraVision DriveSafe

## Como Funciona

1. **Criação automática**:
   - Marca ZEISS é criada automaticamente se não existir
   - Fornecedor é criado ou usado o fornecedor especificado
   - Preços são cadastrados para todas as lojas ativas

2. **Códigos de produtos**:
   - Códigos são gerados automaticamente usando o prefixo do tipo de produto (L para Lentes)

3. **Preços**:
   - Preços são extraídos diretamente do catálogo
   - Custo é estimado em 50% do preço de venda

4. **Duplicatas**:
   - Produtos duplicados são automaticamente ignorados

## Exemplo de Saída

```
📄 Processando catálogo ZEISS...
✓ Encontrados 250 produtos únicos
📦 Iniciando cadastro de produtos...

✅ Importação concluída!
┌─────────────────────────┬────────────┐
│ Status                  │ Quantidade │
├─────────────────────────┼────────────┤
│ ✅ Cadastrados          │ 245        │
│ ⚠️  Ignorados           │ 3          │
│ ❌ Erros                │ 2          │
│ 📊 Total processado     │ 250        │
└─────────────────────────┴────────────┘
```

## Observações

- Os produtos são cadastrados como "Lentes" (tipo de produto com prefixo L)
- Todos os produtos são marcados como `sell_only_with_os = true` (vendem apenas com OS)
- Controle de estoque está desabilitado por padrão (`control_stock = false`)
- Unidade de medida é "PAR" (par de lentes)

## Resolução de Problemas

### Erro: "Nenhum tipo de produto encontrado"
Execute o seeder de tipos de produto primeiro:
```bash
php artisan db:seed --class=ProductTypesSeeder
```

### Erro de conexão com banco de dados
Verifique se o arquivo `.env` está configurado corretamente com as credenciais do banco.

### Produtos duplicados
O comando verifica automaticamente se o produto já existe pelo nome e fornecedor. Produtos duplicados são ignorados.

