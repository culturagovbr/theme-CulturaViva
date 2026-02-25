app.component('rcv-home-cards', {
    template: $TEMPLATES['rcv-home-cards'],

    data() {
        const subsite = $MAPAS.subsite;

        return {
            subsite
        }
    },
});
