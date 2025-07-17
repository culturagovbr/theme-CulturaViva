app.component('rcv-pnab-subscription' , {
    template: $TEMPLATES['rcv-pnab-subscription'],

    props: {
        entity: {
            type: Entity,
            required: true
        }
    },

    setup() {
        const text = Utils.getTexts('rcv-pnab-subscription');
        return { text }
    },

    data () {
        let agent = null;
        agent = $MAPAS.user.profile;

        return {
            agent,
            processing: false
        }
    },

    methods: {
        async subscribe() {
            const messages = useMessages();

            this.processing = true;

            const registration = new Entity('registration');
            registration.opportunity = this.entity;

            registration.owner = this.agent;

            registration.disableMessages();
            try {
                await registration.save().then(res => {
                    window.location.href = registration.editUrl;
                });
            } catch (error) {
                if (error.error) {
                    for (let key in error.data) {
                        if (error.data[key] instanceof Array) {
                            for (let val of error.data[key]) {
                                messages.error(val);
                            }
                        }
                        if (!(error.data[key] instanceof Array)) {
                            for (let _key in error.data[key]) {
                                if (error.data[key][_key] instanceof Array) {
                                    for (let _val of error.data[key][_key]) {
                                        messages.error(_val);
                                    }
                                } else {
                                    messages.error(error.data[key][_key]);
                                }
                            }
                        }
                    }

                    this.processing = false;
                }
            }
        },

        redirectLogin() {
            let url = Utils.createUrl('auth', '') + `?redirectTo=${this.entity.singleUrl.pathname}`;
            window.location.href = url;
        }
    }
});