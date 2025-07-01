app.component('rcv-agent-tabs', {
    template: $TEMPLATES['rcv-agent-tabs'],
    props: {
        entity: {
            type: Entity,
            required: true,
        },
    },
    methods: {
        empty (value) {
            if (Array.isArray(value)) {
                return value.length === 0;
            } else {
                return !value;
            }
        },
    },
});
