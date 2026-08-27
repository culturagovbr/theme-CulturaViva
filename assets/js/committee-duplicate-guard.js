(function () {
    const component = app.component('opportunity-evaluation-committee');

    if (!component?.methods) {
        return;
    }

    const originalSelectAgent = component.methods.selectAgent;

    component.methods.selectAgent = async function (agent) {
        const api = new API();
        const url = Utils.createUrl('evaluationMethodConfiguration', 'createAgentRelation', { id: this.entity.id });

        this.agentData = {
            group: this.group,
            agentId: agent._id,
            has_control: true
        };

        const res = await api.POST(url, this.agentData);
        const data = await res.json().catch(() => ({}));

        // o core devolve 403 com mensagem genérica quando a validação do tema barra
        if (!res.ok || data?.error) {
            this.messages.error(this.text('avaliadorJaNaComissao'));
            return;
        }

        this.showOwnRegistrationsWarning(data);
        this.loadReviewers();
        this.loadFetchs();
        this.refreshEntityPermissions();
    };

    // mantém referência para depuração
    component.methods.selectAgent.original = originalSelectAgent;
})();
