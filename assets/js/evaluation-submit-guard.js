(function () {
    const component = app.component('evaluation-actions');

    if (!component?.methods) {
        return;
    }

    // esmaece e bloqueia os botões enquanto a avaliação está sendo gravada
    if (!document.getElementById('rcv-evaluation-actions-guard-style')) {
        const style = document.createElement('style');
        style.id = 'rcv-evaluation-actions-guard-style';
        style.textContent = '.evaluation-actions--busy{pointer-events:none;opacity:.55;transition:opacity .15s}';
        document.head.appendChild(style);
    }

    // uma ação de concluir/enviar/reabrir por vez enquanto a anterior não termina
    let busy = false;
    let release = null;

    function guard(original) {
        return function (...args) {
            if (busy) {
                return;
            }

            busy = true;
            this.$el?.classList?.add('evaluation-actions--busy');

            const finish = () => {
                clearTimeout(release);
                busy = false;
                this.$el?.classList?.remove('evaluation-actions--busy');
            };

            clearTimeout(release);
            // rede de segurança: POST sem resposta não trava os botões pra sempre
            release = setTimeout(finish, 10000);

            let result;
            try {
                result = original.apply(this, args);
            } catch (error) {
                finish();
                throw error;
            }

            // validação barrou a continuidade: o core não envia e a promise não resolve
            if (this.globalState?.validateEvaluationErrors) {
                finish();
                return result;
            }

            Promise.resolve(result).then(finish, finish);
            return result;
        };
    }

    component.methods.finishEvaluation = guard(component.methods.finishEvaluation);
    component.methods.finishEvaluationSend = guard(component.methods.finishEvaluationSend);
    component.methods.finishEvaluationSendLater = guard(component.methods.finishEvaluationSendLater);
    component.methods.reopen = guard(component.methods.reopen);
})();
