app.component('rcv-home-charts', {
    template: $TEMPLATES['rcv-home-charts'],

    props: {
        chartData: {
            type: Array,
            required: true,
            default: () => [
                { name: 'Norte', data: { 2029: { height: 44, value: 0 }, 2030: { height: 44, value: 0 } } },
                { name: 'Nordeste', data: { 2029: { height: 100, value: 735 }, 2030: { height: 100, value: 735 } } },
                { name: 'Centro-oeste', data: { 2029: { height: 125, value: 125 }, 2030: { height: 125, value: 125 } } },
                { name: 'Sudeste', data: { 2029: { height: 137, value: 137 }, 2030: { height: 137, value: 137 } } },
                { name: 'Sul', data: { 2029: { height: 86, value: 86 }, 2030: { height: 86, value: 86 } } }
            ]
        },

        years: {
            type: Array,
            default: () => [2030, 2029]
        },
    },

    data() {
        const subsite = $MAPAS.subsite;

        return {
            subsite
        }
    },

    computed: {
        horizontalLines() {
            const max = 230; 
            const steps = 5;
            return Array.from({ length: steps }, (_, i) => i * (max / (steps - 1)));
        },
        
        yAxisLabels() {
            return [50, 40, 30, 20, 10, 0];
        },
    },

    methods: {
        getBarHeight(segmentHeight) {
            const max = Math.max(...this.chartData.flatMap(region => Object.values(region.data).map(segment => segment.height)));
            return `${(segmentHeight / max) * 100}%`;
        }
    }
});
