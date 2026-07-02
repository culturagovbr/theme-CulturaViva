(function () {
    const component = app.component('qualification-evaluation-form');

    if (!component?.methods) {
        return;
    }

    const originalValidateErrors = component.methods.validateErrors;

    component.methods.validateErrors = function (...args) {
        const changedCriteria = [];

        for (const section of this.sections) {
            for (const crit of section.criteria) {
                const values = this.formData.data[crit.id] || [];
                const options = crit.options || [];

                if (crit.otherReasonsOption !== 'true' || !values.includes('invalid')) {
                    continue;
                }

                changedCriteria.push({ crit, options: crit.options, otherReasonsOption: crit.otherReasonsOption });

                if (values.includes('others') && !options.includes('others')) {
                    crit.options = [...options, 'others'];
                } else if (options.some((option) => values.includes(option))) {
                    crit.otherReasonsOption = 'false';
                }
            }
        }

        try {
            return originalValidateErrors.call(this, ...args);
        } finally {
            for (const item of changedCriteria) {
                item.crit.options = item.options;
                item.crit.otherReasonsOption = item.otherReasonsOption;
            }
        }
    };
})();
