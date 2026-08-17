# 🌙 LUNACASH — AGENT INSTRUCTIONS

## 1. IDENTIDADE DO PROJETO

Você está trabalhando no projeto **LunaCash**.

O LunaCash é uma aplicação web profissional de controle financeiro pessoal.

O objetivo é construir um produto real, seguro, moderno, responsivo, escalável e preparado para produção.

Não trate o LunaCash como um protótipo ou exercício acadêmico.

---

# 2. STACK OBRIGATÓRIA

A stack oficial do projeto é:

* Laravel 12.x
* PHP 8.2+
* Blade
* Livewire 3
* Tailwind CSS
* Vite
* ApexCharts
* Lucide Icons
* PostgreSQL
* Supabase
* GitHub
* Hostinger

Não substituir a stack por:

* React
* Next.js
* Vue
* Angular
* Node.js como backend principal
* Firebase
* MongoDB
* MySQL

sem autorização explícita.

---

# 3. PRINCÍPIO FUNDAMENTAL

Não tente construir todo o LunaCash de uma vez.

O projeto deverá ser desenvolvido incrementalmente.

Antes de implementar uma funcionalidade:

1. Entenda a arquitetura existente.
2. Inspecione os arquivos relacionados.
3. Verifique dependências.
4. Identifique possíveis impactos.
5. Planeje a alteração.
6. Implemente.
7. Teste.
8. Verifique regressões.

Nunca altere grandes partes do projeto sem necessidade.

---

# 4. REGRA DE CONTEXTO

Antes de escrever código, sempre leia os arquivos relevantes existentes.

Não assuma que um arquivo possui determinada estrutura sem verificá-lo.

Não recrie arquivos que já existem.

Não substitua funcionalidades existentes sem verificar seu impacto.

Preserve código funcional.

---

# 5. DESENVOLVIMENTO POR ETAPAS

O projeto deverá seguir esta ordem:

## FASE 1 — FUNDAÇÃO

1. Arquitetura
2. Configuração Laravel
3. Supabase
4. Banco de dados
5. Segurança
6. Autenticação
7. Design System
8. Layout

## FASE 2 — NÚCLEO FINANCEIRO

9. Contas
10. Categorias
11. Receitas
12. Despesas
13. Lançamentos
14. Transferências
15. Recorrências

## FASE 3 — RECURSOS AVANÇADOS

16. Cartões
17. Faturas
18. Parcelamentos
19. Contas a pagar
20. Contas a receber
21. Orçamentos
22. Metas
23. Calendário

## FASE 4 — INTELIGÊNCIA

24. Dashboard
25. Gráficos
26. Relatórios
27. Filtros
28. Pesquisa
29. Notificações
30. Exportação

## FASE 5 — PRODUÇÃO

31. Responsividade
32. Performance
33. Testes
34. Segurança final
35. Deploy
36. Auditoria

Não pule etapas.

---

# 6. REGRA DE PARADA

Quando uma tarefa solicitar uma etapa específica, implemente somente essa etapa.

Depois:

* informe o que foi feito
* informe arquivos criados
* informe arquivos alterados
* informe testes realizados
* informe problemas encontrados
* informe próximos passos

Depois pare.

Não avance automaticamente.

---

# 7. BANCO DE DADOS

O banco de produção será:

Supabase PostgreSQL.

Valores monetários nunca devem utilizar FLOAT.

Utilize tipos adequados para precisão financeira, preferencialmente NUMERIC/DECIMAL.

Utilize:

* UUID quando apropriado
* foreign keys
* indexes
* constraints
* timestamps
* transactions
* soft deletes quando necessário

Não criar tabelas redundantes.

Não criar relacionamentos desnecessários.

---

# 8. SEGURANÇA

Segurança é prioridade.

Cada usuário deverá acessar somente os próprios dados.

Utilizar:

* autenticação
* autorização
* policies
* validação
* foreign keys
* RLS quando aplicável
* proteção de rotas
* proteção de ações
* proteção contra mass assignment

Nunca confiar apenas na interface para segurança.

---

# 9. CREDENCIAIS

Nunca colocar credenciais diretamente no código.

Nunca versionar:

.env

Nunca colocar:

* senhas
* tokens
* chaves privadas
* service role keys

no Git.

Nunca expor credenciais sensíveis no frontend.

Utilizar variáveis de ambiente.

---

# 10. SUPABASE

O Supabase será utilizado principalmente como:

* PostgreSQL
* infraestrutura do banco
* recursos relacionados ao banco
* RLS quando aplicável

O Laravel será o backend principal da aplicação.

Não duplicar desnecessariamente autenticação entre Laravel e Supabase.

---

# 11. REGRAS FINANCEIRAS

A integridade financeira é crítica.

Uma transferência entre contas NÃO é uma receita nem uma despesa.

Exemplo:

Conta A
→ R$ 500
→ Conta B

Deve resultar em:

Conta A: -R$ 500

Conta B: +R$ 500

Sem criar artificialmente:

+R$ 500 receita
-R$ 500 despesa

---

# 12. CONSISTÊNCIA DOS SALDOS

Qualquer operação que afete dinheiro deve considerar:

* saldo inicial
* receitas
* despesas
* transferências
* pagamentos
* faturas
* parcelas

Evite alterações parciais.

Quando necessário utilize database transactions.

---

# 13. MODELOS FINANCEIROS

O sistema deverá ser preparado para trabalhar com:

* contas
* categorias
* subcategorias
* receitas
* despesas
* lançamentos
* transferências
* cartões
* faturas
* parcelas
* recorrências
* contas a pagar
* contas a receber
* orçamentos
* metas
* notificações

---

# 14. ARQUITETURA LARAVEL

Utilize corretamente:

* Models
* Controllers
* Form Requests
* Policies
* Enums
* Services
* Livewire Components
* Events somente quando necessário
* Jobs somente quando necessário
* Actions somente quando agregarem valor

Não colocar toda a lógica em Controllers.

Não criar abstrações desnecessárias.

---

# 15. LIVEWIRE

Utilizar Livewire para interações que não precisam de JavaScript complexo.

Exemplos:

* formulários
* filtros
* tabelas
* paginação
* modais
* ações rápidas
* atualizações
* buscas

Evitar transformar o projeto em SPA sem necessidade.

---

# 16. BLADE

Blade deverá ser utilizado como camada principal de apresentação.

Criar componentes reutilizáveis.

Evitar views gigantes.

Separar responsabilidades.

---

# 17. TAILWIND

Utilizar Tailwind CSS de maneira consistente.

Evitar CSS duplicado.

Evitar estilos inline desnecessários.

Manter consistência de:

* espaçamento
* tipografia
* cores
* bordas
* radius
* sombras
* estados

---

# 18. DESIGN

O LunaCash deverá possuir aparência:

* moderna
* minimalista
* elegante
* premium
* fintech
* profissional

Não criar uma interface genérica de painel administrativo.

Priorizar:

* hierarquia visual
* legibilidade
* espaçamento
* consistência
* simplicidade

---

# 19. TEMAS

Suportar:

* Light Mode
* Dark Mode

A identidade visual deve permanecer consistente.

---

# 20. ÍCONES

Utilizar Lucide Icons.

Não utilizar emojis como ícones principais da interface.

O símbolo da lua pode ser utilizado na identidade da marca.

---

# 21. GRÁFICOS

Utilizar ApexCharts.

Os gráficos deverão ser:

* responsivos
* interativos
* legíveis
* performáticos
* alimentados por dados reais

Não criar gráficos falsos quando os dados reais estiverem disponíveis.

---

# 22. RESPONSIVIDADE

Toda funcionalidade deve considerar:

* desktop
* notebook
* tablet
* celular

Não assumir que o usuário estará sempre em desktop.

---

# 23. UX

Utilizar quando necessário:

* loading states
* skeletons
* empty states
* error states
* success states
* modais
* confirmações
* toasts
* tooltips
* validações

Nunca deixar o usuário sem feedback após uma ação.

---

# 24. FORMATAÇÃO

Idioma:

pt-BR

Moeda:

BRL

Exemplo:

R$ 1.250,50

Data:

11/08/2026

Percentual:

45,50%

---

# 25. PERFORMANCE

Evitar:

* N+1 queries
* queries desnecessárias
* consultas gigantes
* JavaScript desnecessário
* componentes excessivamente pesados

Utilizar:

* eager loading
* paginação
* índices
* cache quando apropriado
* queries eficientes

---

# 26. HOSTINGER

O projeto deverá permanecer compatível com hospedagem Laravel na Hostinger.

Evitar infraestrutura complexa sem necessidade.

Não introduzir:

* Kubernetes
* Docker obrigatório
* Redis obrigatório
* workers obrigatórios
* servidores Node permanentes

sem necessidade técnica real.

---

# 27. PRODUÇÃO

Em produção:

APP_ENV=production

APP_DEBUG=false

Nunca expor stack traces ou informações internas.

---

# 28. VITE

O frontend de produção deverá utilizar os assets compilados.

Não depender do Vite Dev Server em produção.

O build deverá ser compatível com:

npm run build

---

# 29. TESTES

Testar principalmente:

* autenticação
* autorização
* criação de receita
* criação de despesa
* transferência
* saldo
* cartão
* fatura
* parcela
* orçamento
* meta
* isolamento entre usuários

---

# 30. ALTERAÇÕES

Antes de alterar um arquivo:

* leia o arquivo
* entenda sua função
* procure referências
* verifique dependências

Não apagar código funcional sem motivo.

---

# 31. ERROS

Se encontrar um erro:

Explique:

ERRO:
[descrição]

CAUSA:
[causa]

IMPACTO:
[impacto]

CORREÇÃO:
[correção]

Depois aplique a correção quando estiver dentro do escopo da tarefa.

---

# 32. DOCUMENTAÇÃO DO PROJETO

Quando uma decisão arquitetural importante for tomada, documente-a.

Não dependa somente da memória da conversa.

Utilize documentação dentro do repositório quando necessário.

---

# 33. REGRA DE CÓDIGO

Prefira:

código simples

sobre:

código excessivamente abstrato.

Prefira:

reutilização real

sobre:

abstrações prematuras.

Prefira:

clareza

sobre:

complexidade.

---

# 34. REGRA DO AGENTE

Você é o implementador do projeto.

Não deve simplesmente explicar como fazer quando puder executar a alteração diretamente no workspace.

Quando a tarefa for claramente de implementação:

1. inspecione
2. planeje
3. implemente
4. teste
5. valide

---

# 35. NÃO INVENTAR

Não invente:

* APIs
* tabelas
* campos
* comandos
* bibliotecas
* configurações

sem verificar o projeto ou a documentação disponível.

Se uma decisão técnica importante estiver indefinida, explique antes de implementá-la.

---

# 36. REGRA DE DEPENDÊNCIAS

Antes de adicionar uma biblioteca:

1. verifique se ela é realmente necessária
2. verifique se Laravel/Livewire/Tailwind já resolve o problema
3. considere manutenção
4. considere compatibilidade com Hostinger
5. considere tamanho/performance

Não adicionar dependências apenas por conveniência.

---

# 37. GIT

Preservar o histórico do projeto.

Não executar operações destrutivas como:

* git reset --hard
* apagar branches
* reescrever histórico
* force push

sem autorização explícita.

---

# 38. STATUS

Ao concluir uma etapa, apresentar:

========================================
🌙 LUNACASH STATUS
==================

Etapa:
[etapa]

Objetivo:
[objetivo]

Status:
[CONCLUÍDA / PENDENTE]

Arquivos criados:

* ...

Arquivos modificados:

* ...

Testes:

* ...

Problemas:

* ...

Pendências:

* ...

Próximo passo:
[próxima etapa]

========================================

Depois pare.

---

# 39. PRINCÍPIO FINAL

O LunaCash deverá ser construído como um produto real.

Prioridades:

1. Segurança
2. Integridade financeira
3. Arquitetura
4. Manutenibilidade
5. Performance
6. UX
7. Design

Não sacrificar segurança ou integridade financeira em troca de velocidade.

Não sacrificar arquitetura em troca de código rápido.

Não sacrificar usabilidade em troca de complexidade.

---

# 40. COMANDO DE CONTINUIDADE

Quando o usuário disser:

CONTINUAR

identifique a próxima etapa pendente e prossiga somente com ela.

Não reinicie o projeto.

Não refaça etapas concluídas.

Não altere a stack.

Não avance duas ou mais etapas.

---

# FIM DAS INSTRUÇÕES
