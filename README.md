### Plugin Round Robin para GLPI - Atribuição Automática de Técnicos por Categoria Selecionada

O **plugin Round Robin para GLPI** permite configurar uma política de atribuição automática para distribuir tickets de forma equilibrada entre técnicos pertencentes a um grupo específico. Ele distribui a carga de trabalho de maneira justa, assegurando que os tickets sejam atribuídos sequencialmente entre os membros do grupo.

#### Funcionalidades Principais:
1. **Definição de Categorias**:
   - O plugin funciona para categorias ITIL específicas configuradas no campo "Grupo responsável pelo hardware".
   - Quando um ticket com a categoria designada é criado, o plugin identifica o grupo associado e atribui o ticket ao próximo membro do grupo em ordem rotativa.

2. **Ajustes Automáticos**:
   - Caso o grupo ou os membros sejam alterados, o plugin adapta seu comportamento automaticamente para refletir as mudanças.

3. **Opção de Inclusão do Grupo como Atribuído**:
   - Há uma configuração opcional para adicionar o grupo completo como atribuído. Isso é útil em cenários onde outros técnicos podem precisar gerenciar a fila, como em casos de ausência.

#### Benefícios:
- Equilíbrio na distribuição de tarefas entre os técnicos.
- Simplificação da gestão de tickets em categorias específicas.
- Adaptabilidade às mudanças na equipe ou estrutura do grupo.

---

**Observação**: Sou iniciante no GitHub e este é meu primeiro projeto. Estou aprendendo tanto sobre o desenvolvimento de plugins quanto sobre como interagir com a plataforma GitHub. Qualquer feedback ou sugestão será muito bem-vindo! 😊

**Aproveite essa solução prática e eficiente para melhorar a gestão de tickets no GLPI!**
