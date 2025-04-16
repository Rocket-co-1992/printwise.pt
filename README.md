# PrintWise

Sistema de gestão de orçamentos para gráficas.

## Requisitos do Sistema

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Extensão PDO PHP
- mod_rewrite habilitado (Apache)

## Instalação em Alojamento Partilhado

1. **Fazer upload dos ficheiros**:
   - Execute o script `deploy.sh` para criar um pacote de implantação
   - Faça upload do arquivo ZIP gerado para o seu servidor
   - Extraia o arquivo no diretório raiz do seu site

2. **Configuração da Base de Dados**:
   - Crie uma nova base de dados no seu painel de controlo de alojamento
   - Edite o ficheiro `config/database.production.php` com as suas credenciais:
     - nome de utilizador
     - palavra-passe
     - nome da base de dados

3. **Importar a estrutura da Base de Dados**:
   - Importe o ficheiro `config/database.sql` para a sua base de dados usando phpMyAdmin ou ferramentas similares

4. **Configuração do Aplicativo**:
   - Edite o ficheiro `config/config.production.php`:
     - Atualize `app_url` para o seu domínio
     - Configure os detalhes do WhatsApp
     - Configure outros parâmetros conforme necessário

5. **Permissões de Ficheiros**:
   - Defina permissões apropriadas para os diretórios:
     ```
     chmod -R 755 public/
     chmod -R 755 resources/
     ```

6. **Configuração do .htaccess**:
   - Verifique se os ficheiros .htaccess estão presentes na raiz e na pasta public/
   - Se o seu site estiver num subdiretório, ajuste o RewriteBase conforme necessário

## Acesso ao Sistema

Após a instalação, poderá aceder ao sistema usando:

- **URL**: https://seudominio.pt/
- **Login administrativo**: admin@printwise.pt
- **Palavra-passe inicial**: admin123

É recomendado alterar a palavra-passe após o primeiro acesso.

## Solução de Problemas

### URLs não funcionam (erro 404)
- Verifique se o mod_rewrite está ativado no servidor
- Verifique se os ficheiros .htaccess estão presentes e configurados corretamente

### Erros de conexão à base de dados
- Verifique se as credenciais em `config/database.production.php` estão corretas
- Certifique-se que o utilizador da base de dados tem permissões adequadas

### Permissões de ficheiros
- Se houver problemas com upload de ficheiros, verifique as permissões dos diretórios de upload