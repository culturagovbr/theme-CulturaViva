<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

?>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.nomeEmpresarial">
    <span class="single-data__field-label"><?= i::__("Nome empresarial") ?></span>
    {{rfData.nomeEmpresarial}}
</div>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.nomeFantasia">
    <span class="single-data__field-label"><?= i::__("Nome fantasia") ?></span>
    {{rfData.nomeFantasia}}
</div>

<div class="col-4">&nbsp;</div>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.ni">
    <span class="single-data__field-label"><?= i::__("Número de inscrição") ?></span>
    {{formatCNPJ(rfData.ni)}}
</div>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.dataAbertura">
    <span class="single-data__field-label"><?= i::__("Data de abertura") ?></span>
    {{formatDate(rfData.dataAbertura)}}
</div>

<div class="single-data__field col-4 sm:col-6" v-if="rfData.situacaoCadastral">
    <span class="single-data__field-label"><?= i::__("Situação cadastral") ?></span>
    {{rfData.situacaoCadastral.codigo == '2' ? 'Ativa' : 'Inativa'}}
</div>

<div class="single-data__field col-4 sm:col-6" v-if="rfData.situacaoCadastral.codigo !== '2'">
    <span class="single-data__field-label"><?= i::__("Motivo de situação cadastral") ?></span>
    {{rfData.situacaoCadastral.motivo}}
</div>

<div class="single-data__field col-4 sm:col-6" v-if="rfData.situacaoCadastral">
    <span class="single-data__field-label"><?= i::__("Data da situação cadastral") ?></span>
    {{formatDate(rfData.situacaoCadastral.data)}}
</div>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.telefones">
    <span class="single-data__field-label"><?= i::__("Telefone") ?></span>
    <div v-for="(telefone, index) in rfData.telefones" :key="index" class="telefone-item">
        <span>({{ telefone.ddd }}) {{ telefone.numero }}</span>
    </div>
</div>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.correioEletronico">
    <span class="single-data__field-label"><?= i::__("E-mail") ?></span>
    {{rfData.correioEletronico}}
</div>

<div class="single-data__field col-12" v-if="rfData.endereco">
    <span class="single-data__field-label"><?= i::__("Endereço") ?></span>
    <div>
        {{ rfData.endereco.numero }}, {{ rfData.endereco.tipoLogradouro }} {{ rfData.endereco.logradouro }},
        <span v-if="rfData.endereco.complemento">{{ rfData.endereco.complemento }},</span>
        {{ rfData.endereco.bairro }},
        {{ rfData.endereco.municipio.descricao }},
        {{ rfData.endereco.uf }},
        {{ rfData.endereco.cep }},
        {{ rfData.endereco.pais.descricao }}.
    </div>
</div>

<div class="single-data__field col-12" v-if="rfData.endereco">
    <span class="single-data__field-label"><?= i::__("Domicilio") ?></span>
    {{ rfData.endereco.municipio.descricao }} - {{ rfData.endereco.pais.descricao }}/{{ rfData.endereco.uf }}
</div>

<div class="col-12">
    <hr class="single-data__line">
</div>

<div class="single-data__field col-12" v-if="rfData.capitalSocial">
    <span class="single-data__field-label"><?= i::__("Capital Social da Empresa") ?></span>
    R$: {{rfData.capitalSocial}}
</div>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.naturezaJuridica">
    <span class="single-data__field-label"><?= i::__("Natureza jurídica") ?></span>
    {{rfData.naturezaJuridica.descricao}}
</div>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.informacaoesAdicionais?.optanteSimples">
    <span class="single-data__field-label"><?= i::__("Regime tributário") ?></span>
    {{getTaxRegime(rfData.informacaoesAdicionais?.optanteSimples)}}
</div>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.informacaoesAdicionais?.optanteMei">
    <span class="single-data__field-label"><?= i::__("Porte do estabelecimento") ?></span>
    {{getBusinessSize(rfData.informacaoesAdicionais?.optanteMei)}}
</div>

<div class="single-data__field col-12" v-if="rfData.socios">
    <span class="single-data__field-label"><?= i::__("Quadro Societário") ?></span>

    <div class="grid-12">

        <div v-for="(socio, index) in rfData.socios" :key="index" class="single-data__corporate-structure col-8 sm:col-12 grid-12">
            <!-- Nome do sócio -->
            <div class="single-data__corporate-data col-6">
                <span class="single-data__corporate-data-label">Nome do sócio</span>
                <span>{{ socio.nome }}</span>
            </div>

            <!-- Data de entrada -->
            <div class="single-data__corporate-data col-6">
                <span class="single-data__corporate-data-label"><?= i::__("Data de entrada:") ?></span>
                <span>{{ formatDate(socio.dataInclusao) }}</span>
            </div>

            <!-- Capital social (fixo ou dinâmico) -->
            <div class="single-data__corporate-data col-6">
                <span class="single-data__corporate-data-label"><?= i::__("Capital social:") ?></span>
                <span>R$: 25.000,00</span> <!-- Caso você tenha essa informação, substitua o valor fixo -->
            </div>

            <!-- Participação (fixo ou dinâmico) -->
            <div class="single-data__corporate-data col-6">
                <span class="single-data__corporate-data-label"><?= i::__("Participação:") ?></span>
                <span>50%</span> <!-- Caso você tenha essa informação, substitua o valor fixo -->
            </div>

            <!-- Qualificação -->
            <div class="single-data__corporate-data col-6">
                <span class="single-data__corporate-data-label"><?= i::__("Qualificação:") ?></span>
                <span>{{ socio.qualificacao }}</span>
            </div>

            <!-- País de nascimento -->
            <div class="single-data__corporate-data col-6">
                <span class="single-data__corporate-data-label"><?= i::__("Pais de nascimento:") ?></span>
                <span>{{ socio.pais.descricao }}</span>
            </div>
        </div>
    </div>
</div>

<div class="single-data__field col-12" v-if="rfData.cnaePrincipal">
    <span class="single-data__field-label"><?= i::__("CNAE - Principal") ?></span>
    {{rfData.cnaePrincipal.codigo}} ({{rfData.descricao}})
</div>

<div class="single-data__field col-12" v-if="rfData.cnaeSecundarias && rfData.cnaeSecundarias.length > 0">
    <span class="single-data__field-label"><?= i::__("CNAE secundários (até 10)") ?></span>
    <div v-for="(cnae, index) in rfData.cnaeSecundarias" :key="index">
      {{ cnae.codigo }} ({{ cnae.descricao }})
    </div>
</div>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.situacaoPrincipal">
    <span class="single-data__field-label"><?= i::__("Situação especial") ?></span>
    {{rfData.situacaoPrincipal}}
</div>

<div class="single-data__field col-4 sm:col-12" v-if="rfData.dataSituacaoEspecial">
    <span class="single-data__field-label"><?= i::__("Data da situação especial") ?></span>
    {{rfData.dataSituacaoEspecial}}
</div>