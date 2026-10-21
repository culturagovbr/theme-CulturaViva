(function () {
    const component = app.component('registration-status');

    if (!component?.computed?.showRegistrationResults || !component?.methods?.showResults) {
        return;
    }

    const originalShowRegistrationResults = component.computed.showRegistrationResults;
    const originalShowResults = component.methods.showResults;

    function adminCanView(registration) {
        return !!$MAPAS.config?.registrationResults?.adminCanViewEvaluationResults?.[registration?.id];
    }

    // botão "Exibir detalhamento"
    component.computed.showRegistrationResults = function (...args) {
        return originalShowRegistrationResults.call(this, ...args) || adminCanView(this.registration);
    };

    // bloco do resultado da fase, que envolve o botão
    component.methods.showResults = function (...args) {
        return originalShowResults.call(this, ...args) || adminCanView(this.registration);
    };
})();
