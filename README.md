# Reserva de Salas de Reunião

Sistema web para agendamento de salas de reunião, desenvolvido para uso interno em uma indústria e **em produção** desde 2026.

Substituiu o controle informal de reservas por uma agenda única, com horários visíveis para todos e relatório de uso para a gestão.

## Funcionalidades

**Usuários**
- Login com e-mail e senha (contas criadas pelo administrador, sem auto-cadastro)
- Grade de horários por sala, em blocos de 30 minutos
- Reserva e cancelamento das próprias reservas
- Página "Minhas reservas"

**Administrador**
- Cadastro e edição de salas
- Cadastro e gestão de usuários
- Visualização e cancelamento de todas as reservas
- Relatório de uso das salas

## Tecnologias

- PHP 8
- MySQL
- HTML e CSS
- Hospedagem compartilhada (sem etapa de build)

## Estrutura

| Arquivo | Função |
|---|---|
| `index.php` | Lista de salas |
| `sala.php` | Grade de horários e reserva de uma sala |
| `minhas-reservas.php` | Reservas do usuário logado |
| `login.php` / `logout.php` | Autenticação |
| `config.example.php` | Modelo de configuração do banco |
| `schema.sql` | Criação das tabelas |

## Como rodar

1. Crie um banco MySQL e importe o `schema.sql`.
2. Copie `config.example.php` para `config.php` e preencha com os dados do seu banco.
3. Envie os arquivos para um servidor com PHP 8.
4. Crie o primeiro usuário administrador diretamente no banco, com a senha gerada por `password_hash()`.

## Telas

<!-- Adicione aqui 2 ou 3 prints: login, grade de horários e painel admin -->
<!-- Exemplo: ![Grade de horários](img/grade.png) -->

## Autor

Douglas Carias · [LinkedIn](https://linkedin.com/in/douglas-carias-8342651b3)
