app.component('rcv-home-entities', {
    template: $TEMPLATES['rcv-home-entities'],

    data() {
        const subsite = $MAPAS.subsite;

        return {
            subsite
        }
    },
});
