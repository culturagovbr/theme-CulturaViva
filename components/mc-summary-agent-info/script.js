app.component('mc-summary-agent-info', {
    template: $TEMPLATES['mc-summary-agent-info'],

    props: {
        entity:{
            type: Entity,
            required: true
        },
        classes: {
            type: [String, Array, Object],
            required: false
        },
    },
    data() {
        let avaliableEvaluationFields = $MAPAS.avaliableEvaluationFields
        let opportunity = this.entity.opportunity;
        let owner = this.entity.agentsData.owner;
        let colective = this.entity.agentsData?.coletivo || this.entity.agentsData?.ponto || this.entity.agentsData?.instituicao;
        
        return { avaliableEvaluationFields, owner, colective, opportunity }
    },
    methods: {
        canSee(item) {
            let can = false;
            if(this.entity.currentUserPermissions['@control']){
                can = true
            }

            if (can && !this.avaliableEvaluationFields[item]) {
                can = false;
            }
            
            return can;
        },
        getAvatarRelatedEntity(type) {
            var avatar = null;
            if (this.entity.agentRelations && this.entity.agentRelations.hasOwnProperty(type)) {
                this.entity.agentRelations[type].forEach(element => {
                    var id = this.entity.agentsData?.[type]?.id;
                    if (id == element.agent.id) {
                        if (element.agent?.files?.avatar) {
                            avatar = element.agent.files.avatar.url
                        }
                    }
                });
            }

            return avatar;
        },
        formatCNPJ(cnpj) {
            cnpj = cnpj.replace(/\D/g, '');
        
            if (cnpj.length === 14) {
                return cnpj.replace(
                    /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
                    '$1.$2.$3/$4-$5'
                );
            }
        }
    },
});
