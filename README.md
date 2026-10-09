# Eficaz B2B — MVP de aquisição e gestão de estoque

Monólito acadêmico em **PHP 8.2 + Laravel 12 + Blade + Tailwind + MySQL**. O front-end, regras de negócio e banco vivem neste único projeto.

---

## Fluxo demonstrável

```text
Revendedor: login → busca por SKU → carrinho → orçamento → pedido → status
Administrador: login → dashboard → produtos / revendedores / pedidos
```

O escopo vem do guia BMAD fornecido: não há ERP real, nota fiscal, pagamento, frete, promoções avançadas, microserviços, filas, Redis ou IA.

---

## Estrutura do projeto

```text
app/
  Http/Controllers/     ações HTTP por área
  Http/Middleware/      separação ADMIN e REVENDEDOR
  Http/Requests/        validação no servidor
  Models/               entidades Eloquent
  Services/             preço, orçamento e pedido
database/
  migrations/           esquema MySQL
  seeders/              dados de demonstração
resources/views/        telas Blade e componentes
routes/web.php          rotas do portal
```

---

## Modelo de dados

`users` possui o papel `ADMIN` ou `REVENDEDOR`. Cada revendedor possui um perfil comercial (`STANDARD`, `GOLD`, `PREMIUM`), um carrinho, orçamentos e pedidos. Produtos têm SKU único, preço, estoque e situação ativa. Itens de orçamento e pedido guardam snapshots de SKU, nome e valores para preservar o histórico.

O banco garante que `orders.quote_id` seja único: um orçamento só pode gerar um pedido.

---

## Decisões explícitas do esqueleto

O guia não define baixa/reserva de estoque, validade/cancelamento de orçamento, frete, impostos ou regra de crédito. Portanto, este MVP **valida o estoque** ao incluir itens e converter o pedido, mas **não baixa nem reserva estoque**. Ao gerar orçamento, o carrinho é limpo e os itens/valores ficam congelados no orçamento. Essas são decisões técnicas documentadas, não requisitos adicionais do guia.

---

## 🚀 Como Inicializar e Executar Localmente

### 📋 Pré-requisitos

| Requisito | Versão Mínima | Observações |
|---|---|---|
| **PHP** | 8.2+ | Com extensões: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `xml`, `zip` |
| **Composer** | 2.x+ | Gerenciador de dependências PHP |
| **Node.js** | 22+ | E `npm` correspondente para compilação dos ativos front-end |
| **Docker** | Docker Desktop (Win/macOS) / Docker Engine (Linux) | Para o banco de dados MySQL 8.4 local |

---

### 💻 Passo a Passo de Inicialização

Escolha as instruções conforme o seu sistema operacional ou ambiente:

#### 🐧 Linux / macOS (Terminal / Bash / Zsh)

```bash
# 1. Clonar o repositório e acessar a pasta
git clone <URL_DO_REPOSITORIO>
cd Eficaz-System

# 2. Instalar dependências do PHP
composer install

# 3. Copiar o arquivo de configuração de ambiente
cp .env.example .env

# 4. Subir o container MySQL via Docker
docker compose up -d

# (Aguarde ~5 segundos para o MySQL inicializar antes do próximo passo)

# 5. Gerar a chave da aplicação
php artisan key:generate

# 6. Rodar as migrations e popular o banco com dados iniciais
php artisan migrate --seed

# 7. Instalar dependências do Node.js e compilar os assets
npm install
npm run build

# 8. Iniciar o servidor local
php artisan serve
```

---

#### 🪟 Windows (PowerShell / Windows Terminal)

```powershell
# 1. Clonar o repositório e acessar a pasta
git clone <URL_DO_REPOSITORIO>
cd Eficaz-System

# 2. Instalar dependências do PHP
composer install

# 3. Copiar o arquivo de configuração de ambiente
copy .env.example .env

# 4. Subir o container MySQL via Docker Desktop
docker compose up -d

# (Aguarde ~5 segundos para o MySQL inicializar antes do próximo passo)

# 5. Gerar a chave da aplicação
php artisan key:generate

# 6. Rodar as migrations e popular o banco com dados iniciais
php artisan migrate --seed

# 7. Instalar dependências do Node.js e compilar os assets
npm install
npm run build

# 8. Iniciar o servidor local
php artisan serve
```

---

#### 🪟 Windows (Prompt de Comando - CMD)

```cmd
:: 1. Clonar o repositório e acessar a pasta
git clone <URL_DO_REPOSITORIO>
cd Eficaz-System

:: 2. Instalar dependências do PHP
composer install

:: 3. Copiar o arquivo de configuração de ambiente
copy .env.example .env

:: 4. Subir o container MySQL via Docker Desktop
docker compose up -d

:: (Aguarde ~5 segundos para o MySQL inicializar antes do próximo passo)

:: 5. Gerar a chave da aplicação
php artisan key:generate

:: 6. Rodar as migrations e popular o banco com dados iniciais
php artisan migrate --seed

:: 7. Instalar dependências do Node.js e compilar os assets
npm install
npm run build

:: 8. Iniciar o servidor local
php artisan serve
```

---

#### 🌐 Comando Agnóstico (Universal - Funciona em qualquer Shell / SO)

Caso prefira um comando independente da sintaxe do terminal (`cp` vs `copy`):

```bash
composer install
php -r "copy('.env.example', '.env');"
docker compose up -d
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

---

### ⚡ Desenvolvimento Simultâneo (Laravel + Vite HMR)

Para desenvolver com reload automático do front-end e servidor ativado:

```bash
# Executa o servidor PHP e o Vite simultaneamente (requer 'concurrently')
composer dev
```

Ou em dois terminais separados:
- Terminal 1: `php artisan serve`
- Terminal 2: `npm run dev`

---

## 🗄️ Configuração do Banco de Dados (Docker)

O `docker-compose.yml` pré-configurado expõe o MySQL 8.4 na porta **3308** (`127.0.0.1:3308`), já configurada no `.env.example`:

- **Host:** `127.0.0.1`
- **Porta:** `3308`
- **Banco de Dados:** `eficaz_b2b`
- **Usuário:** `eficaz`
- **Senha:** `eficaz`

*Se preferir utilizar uma instância local própria do MySQL (sem Docker), basta ajustar as variáveis `DB_*` no seu `.env` local.*

---

## 🔑 Dados de demonstração

| Perfil | E-mail | Senha |
|---|---|---|
| Administrador | `admin@eficaz.test` | `password` |
| Revendedor GOLD | `revendedor@eficaz.test` | `password` |

> ⚠️ Essas contas são exclusivas para ambiente de demonstração. Não use essas credenciais em produção.

---

## 🛠️ Solução de Problemas Comuns

### 1. Erro ao conectar ao Banco de Dados (`SQLSTATE[HY000] [2002]`)
- **Causa:** O container do MySQL ainda está em processo de inicialização quando as migrations são executadas.
- **Solução:** Aguarde de 5 a 10 segundos após rodar `docker compose up -d` e tente executar `php artisan migrate --seed` novamente.

### 2. Porta `3308` em uso
- **Causa:** Outro container ou serviço local está ocupando a porta 3308.
- **Solução:** Altere a porta mapeada no `docker-compose.yml` (ex: `"3309:3306"`) e reflita essa mudança na variável `DB_PORT` dentro do seu `.env`.

### 3. Erro de Permissão no Linux (`storage` / `bootstrap/cache`)
- **Causa:** Permissões de escrita inadequadas no diretório do projeto.
- **Solução:**
  ```bash
  chmod -R 775 storage bootstrap/cache
  ```

### 4. Permissão do Docker no Linux (`permission denied while trying to connect to the Docker daemon socket`)
- **Solução:** Adicione seu usuário ao grupo `docker`:
  ```bash
  sudo usermod -aG docker $USER
  ```
  *(Após o comando, faça logoff e login novamente).*

### 5. `cp` não é reconhecido no CMD do Windows
- **Solução:** Utilize `copy .env.example .env` ou o comando universal em PHP `php -r "copy('.env.example', '.env');"`.

---

## 🧪 Verificação e Testes

Para rodar a suíte de testes automatizados:

```bash
# Execução via Composer (limpa cache de config e roda a suíte)
composer test

# Ou direto via PHPUnit
./vendor/bin/phpunit
```

*Nota: Os testes automatizados utilizam banco de dados **SQLite em memória**, não afetando a sua base MySQL local.*
