app.component('rcv-registration-info', {
    template: $TEMPLATES['rcv-registration-info'],

    props: {
        registration: {
            type: Entity,
            required: true
        },
        classes: {
            type: [String, Array, Object],
            required: false
        },
    },

    data() {
        return {
            showInfo: false,
        }
    },

    methods: {
        toggleInfo() {
            this.showInfo = !this.showInfo;
        },
    },
});
