# Desafio Técnico - Full Stack PHP

## Visão geral

Este projeto entrega uma aplicação financeira completa, com foco em cadastro, autenticação, movimentação de saldo entre usuários e depósitos, atendendo aos requisitos do desafio técnico.

A solução foi construída com **Laravel 13** no backend e **Vue 3 + Inertia.js** no frontend, priorizando uma arquitetura clara, manutenção simples, validação consistente de regras de negócio e rastreabilidade das operações financeiras.

## Objetivo do sistema

A aplicação simula uma carteira financeira na qual o usuário pode:

- criar conta;
- autenticar-se no sistema;
- enviar e receber valores;
- realizar depósitos;
- reverter operações quando necessário;
- manter consistência mesmo em cenários de falha ou concorrência.

O foco principal do projeto é garantir que cada movimentação financeira seja processada com segurança, precisão e histórico auditável.

## Stack utilizada

### Backend
- PHP 8.5
- Laravel 13
- laravel/fortify
- laravel/prompts
- laravel/wayfinder
- laravel/boost
- laravel/mcp
- laravel/pail
- laravel/pint
- laravel/sail

### Frontend
- Inertia.js v3
- @inertiajs/vue3 v3
- Vue 3
- Tailwind CSS v4
- @laravel/vite-plugin-wayfinder

### Qualidade e testes
- Larastan v3
- Pest v4
- PHPUnit v12
- Rector v2
- ESLint v9
- Prettier v3

## Arquitetura da aplicação

A aplicação foi estruturada para manter separação entre domínio, interface e regras financeiras, com foco em legibilidade e evolução contínua.

### Camadas principais

- **Interface Web**: páginas e formulários renderizados via Inertia.js + Vue 3.
- **Aplicação**: coordenação dos fluxos de cadastro, autenticação e operações financeiras.
- **Domínio**: regras de negócio para saldo, transferência, depósito e reversão.
- **Persistência**: banco de dados relacional com foco em integridade e auditoria.

### Decisões arquiteturais

A solução foi pensada para reduzir acoplamento, centralizar regras críticas no domínio financeiro e facilitar testes unitários e de integração. Isso permite que novas regras sejam adicionadas com menor risco de impacto sobre o restante do sistema.

## Arquitetura financeira: Ledger, Subledger e Microns

Para operações financeiras, o sistema utiliza uma estrutura baseada em **ledger**, **subledger** e **microns**. Essa abordagem é mais confiável do que trabalhar apenas com um saldo final gravado diretamente na conta, porque preserva histórico, melhora a auditoria e evita erros de arredondamento.

### Microns

Todos os valores monetários são armazenados em **microns**, ou seja, como inteiros com precisão de 6 casas decimais.

Exemplo:

- `R$ 10,50` → `10500000`
- `R$ 0,000001` → `1`

#### Por que isso é importante

Usar inteiros em vez de números decimais em ponto flutuante evita perdas de precisão, arredondamentos inconsistentes e diferenças de comportamento entre linguagens, drivers e bancos de dados. Em operações financeiras, isso é essencial para garantir que cada centavo seja calculado corretamente.

### Ledger

O **ledger** é o registro principal de movimentação financeira. Ele representa o efeito contábil da operação sobre a conta, registrando entradas e saídas de forma organizada e rastreável.

### Subledger

O **subledger** é o detalhamento da movimentação vinculada à operação original. Ele permite registrar o contexto da transação, como origem, destino, tipo de operação, valores envolvidos e eventuais reversões.

Em uma transferência, por exemplo, podem existir dois lançamentos relacionados:

- débito para a conta de origem;
- crédito para a conta de destino.

### Benefícios dessa estrutura

#### 1. Precisão financeira
Os microns garantem cálculos exatos e eliminam erros comuns de arredondamento.

#### 2. Rastreabilidade completa
Cada alteração de saldo pode ser associada à operação que a originou, o que facilita auditoria e investigação de inconsistências.

#### 3. Reversão segura
Como as operações ficam registradas em lançamentos, é possível reverter movimentações sem apagar histórico, preservando a trilha contábil.

#### 4. Consistência de dados
A combinação de ledger, subledger e transações atômicas reduz drasticamente o risco de saldo incorreto em cenários de falha.

#### 5. Melhor evolução do domínio financeiro
Essa estrutura facilita a futura implementação de taxas, estornos, conciliações, limites, juros e relatórios contábeis.

### Exemplo prático

Ao transferir `R$ 100,00` da Conta A para a Conta B:

1. a transação é validada;
2. o valor é convertido para microns;
3. são criados os registros de ledger e subledger;
4. o saldo da conta de origem é reduzido;
5. o saldo da conta de destino é aumentado;
6. tudo é executado dentro de uma transação de banco.

Se qualquer etapa falhar, a operação inteira é revertida.

## Regras de negócio

- O usuário precisa estar autenticado para operar no sistema.
- A transferência só é permitida quando houver saldo suficiente.
- Caso a conta esteja negativa por algum motivo, o depósito deve primeiro compensar esse saldo.
- Toda operação financeira deve poder ser revertida em caso de inconsistência.
- O histórico das movimentações deve permanecer íntegro.

## Segurança e integridade

Foram priorizados mecanismos para proteger o fluxo financeiro e reduzir falhas operacionais:

- validações de entrada;
- transações atômicas no banco;
- estrutura contábil com histórico;
- separação clara entre criação da operação e seu processamento;
- base preparada para testes automatizados.

## Qualidade de código

A qualidade do código foi reforçada com:

- **Pint** para padronização;
- **Larastan** para análise estática;
- **Rector** para refatorações assistidas;
- **Pest** e **PHPUnit** para testes;
- **ESLint** e **Prettier** para consistência no frontend.

## Ambiente de desenvolvimento

O projeto utiliza **Laravel Sail** para simplificar a execução local com Docker, garantindo maior previsibilidade entre ambientes.

### Pré-requisitos

- Docker
- Composer
- Node.js
- npm

### Instalação

```bash
git clone <url-do-repositorio>
cd <nome-do-projeto>
cp .env.example .env
composer install
npm install
npm run build
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

## Testes

A suíte de testes foi pensada para validar principalmente:

- autenticação;
- cadastro;
- depósitos;
- transferências;
- reversões;
- consistência de saldo;
- regras financeiras do domínio.

Execução:

```bash
./vendor/bin/sail artisan test
```

## Considerações finais

A proposta do projeto é demonstrar domínio do ecossistema Laravel e, principalmente, a capacidade de modelar um fluxo financeiro com responsabilidade técnica. A escolha por **ledger**, **subledger** e **microns** fortalece a confiabilidade da aplicação, facilita auditoria e torna o sistema mais preparado para crescer sem comprometer a integridade dos dados.
