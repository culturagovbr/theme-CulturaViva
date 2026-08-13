// Utilitários compartilhados pelos componentes do tema
globalThis.$RCV = globalThis.$RCV ?? {};

// Monta os itens de um multiselect de filtro no formato {valor: rótulo}, escapando as vírgulas do
// valor, que os filtros IN()/IIN() da API usam como separador (ver ApiQuery::splitParam no core)
$RCV.filterItems = function (values) {
    const items = {};

    for (const value of values) {
        items[String(value).replace(/,/g, '\\,')] = value;
    }

    return items;
};

// Idem, a partir dos termos de uma taxonomia
$RCV.taxonomyFilterItems = function (slug) {
    return $RCV.filterItems($TAXONOMIES[slug]?.terms ?? []);
};
