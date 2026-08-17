# LunaCash — Project Instructions

Este repositório contém o LunaCash, uma aplicação profissional de controle financeiro.

## Stack

* Laravel 12.x
* PHP 8.2+
* Blade
* Livewire 3
* Tailwind CSS
* Vite
* ApexCharts
* Lucide Icons
* Supabase PostgreSQL
* GitHub
* Hostinger

## Regras principais

* Preserve a arquitetura existente.
* Inspecione arquivos antes de modificá-los.
* Não substitua tecnologias sem autorização.
* Não introduza React, Vue, Next.js ou outro framework frontend.
* Não introduza outro banco de dados.
* Utilize PostgreSQL/Supabase como banco de produção.
* Nunca exponha credenciais.
* Nunca versionar `.env`.
* Utilize validação e autorização.
* Proteja dados por usuário.
* Evite N+1 queries.
* Utilize eager loading quando apropriado.
* Utilize paginação em grandes conjuntos de dados.
* Não utilize FLOAT para valores financeiros.
* Mantenha cálculos financeiros consistentes.
* Transferências não são receitas nem despesas.
* Utilize Livewire para interações server-driven quando apropriado.
* Utilize Blade para apresentação.
* Utilize Tailwind para estilização.
* Utilize ApexCharts para gráficos.
* Utilize Lucide Icons para ícones.
* Mantenha o design responsivo.
* Mantenha compatibilidade com Hostinger.

## Qualidade

Antes de concluir uma alteração:

1. Verifique o código.
2. Verifique possíveis regressões.
3. Execute testes relevantes.
4. Execute lint/format quando disponível.
5. Verifique a integração com os componentes existentes.

Não considere uma tarefa concluída apenas porque o código foi escrito.
