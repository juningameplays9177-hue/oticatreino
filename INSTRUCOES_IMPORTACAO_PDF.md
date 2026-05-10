# Instruções para Importação de Produtos do PDF

## Pré-requisitos

1. **Instalar a biblioteca PDF Parser:**
   ```bash
   composer require smalot/pdfparser
   ```

2. **Configurar o arquivo .env:**
   Certifique-se de que o arquivo `.env` existe e está configurado com as credenciais do banco de dados:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=seu_banco
   DB_USERNAME=seu_usuario
   DB_PASSWORD=sua_senha
   ```

## Como Usar

### 1. Modo Dry-Run (Teste sem salvar)
Primeiro, execute em modo de teste para ver quais produtos serão importados:

```bash
php artisan products:import-pdf "resources/Tabela De Preços SUGERIDO 2026.pdf" --dry-run
```

Este comando irá:
- Extrair o texto do PDF
- Tentar identificar produtos (código, nome, marca, preço)
- Exibir os primeiros 20 produtos encontrados
- **NÃO salvará nada no banco de dados**

### 2. Importação Real
Após verificar que os produtos estão sendo identificados corretamente:

```bash
php artisan products:import-pdf "resources/Tabela De Preços SUGERIDO 2026.pdf" --supplier="Nome do Fornecedor"
```

### 3. Opções Disponíveis

- `file`: Caminho para o arquivo PDF (obrigatório)
- `--supplier=`: Nome do fornecedor padrão (opcional, padrão: "Fornecedor Padrão")
- `--dry-run`: Executa sem salvar no banco (útil para testar)

## Como Funciona

O comando tenta identificar produtos no PDF usando padrões comuns:

1. **Detecção de tabela**: Procura por cabeçalhos como "código", "produto", "descrição", "preço", "marca"
2. **Extração de dados**: Tenta identificar:
   - Código do produto (3-20 caracteres alfanuméricos)
   - EAN13 (13 dígitos)
   - Nome do produto
   - Marca
   - Preço (valores monetários)
3. **Criação automática**:
   - Marcas são criadas automaticamente se não existirem
   - Fornecedor é criado ou usado o fornecedor especificado
   - Preços são cadastrados para todas as lojas ativas

## Ajustes Necessários

Dependendo do formato específico do PDF, você pode precisar ajustar a função `extractProducts()` no arquivo:
`app/Console/Commands/ImportProductsFromPdf.php`

### Verificar Texto Extraído

O comando salva o texto extraído do PDF em:
`storage/logs/pdf_extracted_text.txt`

Você pode usar este arquivo para entender melhor a estrutura do PDF e ajustar a lógica de extração se necessário.

## Resolução de Problemas

### Erro: "Nenhum produto encontrado no PDF"
- Verifique o arquivo `storage/logs/pdf_extracted_text.txt` para ver como o texto foi extraído
- O formato do PDF pode ser diferente do esperado
- Ajuste a função `extractProducts()` conforme necessário

### Erro de conexão com banco de dados
- Verifique se o arquivo `.env` existe e está configurado corretamente
- Teste a conexão: `php artisan tinker` e depois `DB::connection()->getPdo();`

### Produtos duplicados
- O comando verifica se o produto já existe por código, EAN13 ou nome
- Produtos duplicados são automaticamente ignorados

## Exemplo de Saída

```
📄 Processando PDF: resources/Tabela De Preços SUGERIDO 2026.pdf
✓ PDF carregado com sucesso
Total de páginas: 46
Texto extraído salvo em: storage/logs/pdf_extracted_text.txt
✓ Encontrados 1250 produtos
📦 Iniciando cadastro de produtos...

✅ Importação concluída!
┌─────────────────────────┬────────────┐
│ Status                  │ Quantidade │
├─────────────────────────┼────────────┤
│ ✅ Cadastrados          │ 1245       │
│ ⚠️  Ignorados (já existem) │ 3         │
│ ❌ Erros                │ 2          │
│ 📊 Total processado     │ 1250       │
└─────────────────────────┴────────────┘
```

