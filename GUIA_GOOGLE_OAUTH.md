# Guia de Configuração - Autenticação com Google

## Passos para Configurar o Login com Google

### 1. Obter as Credenciais do Google

1. Acesse [Google Developers Console](https://console.cloud.google.com)
2. Clique em "Selecionar um projeto" → "NOVO PROJETO"
3. Digite um nome (ex: "Eduka Plus")
4. Aguarde a criação do projeto
5. No menu lateral, vá para **APIs e Serviços** → **Biblioteca**
6. Procure por "Google+ API" e clique em **ATIVAR**
7. Vá para **Credenciais**
8. Clique em **Criar Credenciais** → **ID do cliente OAuth**
9. Escolha **Aplicação Web**
10. Em **URIs de redirecionamento autorizados**, adicione:
    - `http://localhost/plantaforma/google_callback.php` (desenvolvimento)
    - `https://seu-dominio.com/plantaforma/google_callback.php` (produção)
11. Clique em **Criar**
12. Copie o **ID do Cliente** e a **Chave Secreta do Cliente**

### 2. Configurar as Credenciais no Arquivo

1. Abra o arquivo `google_oauth_config.php`
2. Substitua:
   - `SEU_CLIENT_ID_AQUI` pelo ID do cliente obtido acima
   - `SEU_CLIENT_SECRET_AQUI` pela chave secreta obtida acima
3. Se não estiver em localhost/desenvolvimento, atualize também a `GOOGLE_REDIRECT_URI`

**Exemplo:**
```php
define('GOOGLE_CLIENT_ID', '123456789-abc.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-abcdefghijk');
define('GOOGLE_REDIRECT_URI', 'http://localhost/plantaforma/google_callback.php');
```

### 3. Atualizar o Banco de Dados

Execute o seguinte SQL no seu banco de dados (phpMyAdmin ou via comando MySQL):

```sql
ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(255) UNIQUE DEFAULT NULL;
```

Ou simplesmente execute o arquivo `database_migration.sql` no seu banco de dados.

### 4. Testar o Cadastro com Google

1. Vá para http://localhost/plantaforma/register.php
2. Clique no botão **"Cadastrar com Google"**
3. Você será redirecionado para o Google
4. Faça login com sua conta Google
5. Autorize o acesso
6. Você será automaticamente registrado e redirecionado

## Ficha Técnica

### Arquivos Adicionados

- **google_oauth_config.php** - Configurações e funções do OAuth do Google
- **google_callback.php** - Callback que processa o retorno do Google
- **database_migration.sql** - Script SQL para adicionar coluna ao banco

### Arquivo Modificado

- **register.php** - Adicionado suporte para login com Google

### Fluxo de Autenticação

1. Usuário clica em "Cadastrar com Google"
2. Redirecionado para `google_oauth_config.php` que gera a URL de login
3. Google autentica o usuário
4. Google redireciona de volta para `google_callback.php` com um código
5. `google_callback.php` troca o código por um token
6. Obtém dados do usuário (email, nome)
7. Se o usuário não existe, cria uma nova conta (tipo "aluno")
8. Se o usuário existe, faz login dele
9. Redireciona para o painel apropriado

## Segurança

- O campo `state` é usado para prevenir ataques CSRF
- As credenciais não são expostas no lado do cliente
- Senhas aleatórias são geradas para usuários do Google (não são usadas)
- URLs de redirecionamento são validadas

## Erros Comuns

| Erro | Solução |
|------|---------|
| "Erro de segurança: estado inválido" | O estado CSRF não corresponde. Verifique se as sessões estão funcionando |
| "Client ID não configurado" | Certifique-se de preencher o `GOOGLE_CLIENT_ID` em `google_oauth_config.php` |
| "Erro ao redirecionar" | Verifique se o URL de redirecionamento está correto em ambos: configuração do Google e `google_oauth_config.php` |
| "Column 'google_id' doesn't exist" | Execute o SQL para adicionar a coluna `google_id` na tabela `usuarios` |

## URLs Por Ambiente

**Desenvolvimento (localhost):**
```
http://localhost/plantaforma/google_callback.php
```

**Produção (HTTPS):**
```
https://seu-dominio.com/plantaforma/google_callback.php
```

## Próximos Passos Opcionais

- Implementar login com Facebook (similar ao Google)
- Adicionar autenticação de dois fatores
- Verificar email automático para usuários do Google
- Permitir que usuários editem seu tipo de conta (aluno/professor) após registro
