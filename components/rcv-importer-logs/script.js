app.component('rcv-importer-logs', {
    template: $TEMPLATES['rcv-importer-logs'],

    props: {
        entity: {
            type: Entity,
            required: true
        },
    },

    data() {
        return {
            interval: null,
            importStatus: null
        }
    },

    computed: {
        baseUrl() {
            return $MAPAS.baseURL;
        }
    },
    
    methods: {
        getUrlLogFile(id) {
            let url = Utils.createUrl('site', 'importer-log-view', {id: id});
            return url;
        },

        async getStatusFile() {
            let url = "/files/importer/" + this.entity.id + "_status.json";
            
            try {
                let response = await fetch(`${url}?` + new Date().getTime());
               
                if (response.status == 404) {
                    await fetch("/site/importer-log-create", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({ id: this.entity.id })
                    });

                    return;
                }

                let importStatus = await response.json();
                this.importStatus = importStatus;

                if(importStatus.status !== 1) {
                    clearInterval(this.interval);
                }
            } catch (error) {
                console.error(error);
            }
        },

        getStatusLabel(status) {
            if(status == 1) {
                return 'Importação em andamento';
            }

            if(status == 10) {
                return 'Importação finalizada com sucesso';
            }

            return 'Importação não finalizada';
        }
    },

    mounted() {
        this.getStatusFile();
        
        this.interval = setInterval(() => {
            this.getStatusFile();
        }, 1000);

    },

    unmounted() {
        clearInterval(this.interval);
    },
});
