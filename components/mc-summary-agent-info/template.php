<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-icon    
');
?>

<div class="mc-summary-agent-info" :class="classes">
    <div v-if="opportunity && canSee('agentsSummary')" class="mc-summary-agent-info__section">
        <h3><?= i::__("Dados do proponente") ?></h3>

        <h4 v-if="owner.id"> <span class="bold"><?= i::__("ID:") ?></span> {{owner.id}} </h4>
        <h4 v-if="owner.name"> <span class="bold"><?= i::__("Nome:") ?></span> {{owner.name}} </h4>
        <h4 v-if="owner.location.latitude && owner.location.longitude"> <span class="bold"><?= i::__("Localização:") ?></span> {{owner.location.latitude}}, {{owner.location.longitude}} </h4>
        <h4 v-if="owner.nomeCompleto"> <span class="bold"><?= i::__("Nome completo:") ?></span> {{owner.nomeCompleto}} </h4>
        <h4 v-if="owner.cpf || owner.documento"> <span class="bold"><?= i::__("CPF:") ?></span> {{owner.cpf || owner.documento}} </h4>
        <h4 v-if="owner.raca"> <span class="bold"><?= i::__("Raça/cor:") ?></span> {{owner.raca}} </h4>
        <h4 v-if="owner.dataDeNascimento"> <span class="bold"><?= i::__("Nascimento:") ?></span> {{owner.dataDeNascimento}} </h4>
        <h4 v-if="owner.genero"> <span class="bold"><?= i::__("Gênero:") ?></span> {{owner.genero}} </h4>
        <h4 v-if="owner.emailPublico"> <span class="bold"><?= i::__("Email público:") ?></span> {{owner.emailPublico}} </h4>
        <h4 v-if="owner.emailPrivado"> <span class="bold"><?= i::__("Email Privado:") ?></span> {{owner.emailPrivado}} </h4>
        <h4 v-if="owner.telefonePublico"> <span class="bold"><?= i::__("Telefone público:") ?></span> {{owner.telefonePublico}} </h4>
        <h4 v-if="owner.telefone1"> <span class="bold"><?= i::__("Telefone 1:") ?></span> {{owner.telefone1}} </h4>
        <h4 v-if="owner.telefone2"> <span class="bold"><?= i::__("Telefone 2:") ?></span> {{owner.telefone2}} </h4>
        <h4 v-if="owner.endereco"> <span class="bold"><?= i::__("Endereço:") ?></span> {{owner.endereco}} </h4>
        <h4 v-if="owner.En_CEP"> <span class="bold"><?= i::__("CEP:") ?></span> {{owner.En_CEP}} </h4>
        <h4 v-if="owner.En_Nome_Logradouro"> <span class="bold"><?= i::__("Logradouro:") ?></span> {{owner.En_Nome_Logradouro}} </h4>
        <h4 v-if="owner.En_Num"> <span class="bold"><?= i::__("Número:") ?></span> {{owner.En_Num}} </h4>
        <h4 v-if="owner.complemento"> <span class="bold"><?= i::__("Complemento:") ?></span> {{owner.complemento}} </h4>
        <h4 v-if="owner.En_Bairro"> <span class="bold"><?= i::__("Bairro:") ?></span> {{owner.En_Bairro}} </h4>
        <h4 v-if="owner.En_Municipio"> <span class="bold"><?= i::__("Município:") ?></span> {{owner.En_Municipio}} </h4>
        <h4 v-if="owner.En_Estado"> <span class="bold"><?= i::__("Estado:") ?></span> {{owner.En_Estado}} </h4>
        <h4 v-if="owner.site"> <span class="bold"><?= i::__("Site:") ?></span> {{owner.site}} </h4>
        <h4 v-if="owner.facebook"> <span class="bold"><?= i::__("Facebook:") ?></span> {{owner.facebook}} </h4>
        <h4 v-if="owner.twitter"> <span class="bold"><?= i::__("Twitter:") ?></span> {{owner.twitter}} </h4>
    </div>

    <div v-if="opportunity.useAgentRelationColetivo && opportunity.useAgentRelationColetivo !== 'dontUse'" class="mc-summary-agent-info__section">
        <h3><?= i::__("Dados da organização") ?></h3>

        <div class="mc-summary-agent__agent">
            <div class="mc-avatar--small mc-avatar">
                <img v-if="getAvatarRelatedEntity('coletivo')" :src="getAvatarRelatedEntity('coletivo')" />
            </div>

            <span v-if="colective">{{colective?.name}}</span>            
            <span v-if="!colective && (opportunity.useAgentRelationColetivo)"><?= i::__("Instituição não informada") ?></span>
        </div>

        <div v-if="colective">
            <div><small><strong><?= i::__("ID:") ?></strong> {{colective?.id}}</small></div>
            <div><small><strong><?= i::__("Nome:") ?></strong> {{colective?.name}}</small></div>
            <div><small><strong><?= i::__("Localização:") ?></strong> {{colective?.location.latitude}}, {{colective?.location.longitude}}</small></div>
            <div><small><strong><?= i::__("Telefone 1:") ?></strong> {{colective?.telefone1}}</small></div>
        </div>
    </div>
</div>

<div class="mc-summary-agent-info" :class="classes">
    <div v-if="colective?.rcv_rfData" class="mc-summary-agent-info__section">
        <h3><?= i::__("Dados da receita") ?></h3>

        <div>
            <div v-if="colective?.rcv_rfData?.nomeEmpresarial"><small><strong><?= i::__("Nome empresarial:") ?></strong> {{colective?.rcv_rfData?.nomeEmpresarial}}</small></div>
            <div v-if="colective?.rcv_rfData?.nomeFantasia"><small><strong><?= i::__("Nome fantasia:") ?></strong> {{colective?.rcv_rfData?.nomeFantasia}}</small></div>
            <div v-if="colective?.rcv_rfData?.ni"><small><strong><?= i::__("CNPJ:") ?></strong> {{formatCNPJ(colective?.rcv_rfData?.ni)}}</small></div>
            <div v-if="colective?.rcv_rfData?.correioEletronico"><small><strong><?= i::__("E-mail:") ?></strong> {{colective?.rcv_rfData?.correioEletronico}}</small></div>
            <div v-if="colective?.rcv_rfData?.naturezaJuridica"><small><strong><?= i::__("Natureza jurídica:") ?></strong> {{colective?.rcv_rfData?.naturezaJuridica.descricao}}, {{colective?.rcv_rfData?.naturezaJuridica.codigo}}</small></div>
            <div v-if="colective?.rcv_rfData?.telefones">
                <small v-for="(telefone, index) in colective?.rcv_rfData?.telefones" :key="index">
                    <strong><?= i::__("Telefone") ?> {{index+1}}:</strong> ({{telefone.ddd}}) {{telefone.numero}}
                </small>
            </div>
            <div v-if="colective?.rcv_rfData?.municipioJurisdicao?.descricao"><small><strong><?= i::__("Município de jurisdição:") ?></strong> {{colective?.rcv_rfData?.municipioJurisdicao?.descricao}}</small></div>
            <div v-if="colective?.rcv_rfData?.endereco">
                <small>
                    <strong><?= i::__("Endereço:") ?></strong><br>
                    {{colective?.rcv_rfData?.endereco?.tipoLogradouro}} {{colective?.rcv_rfData?.endereco?.logradouro}}, {{colective?.rcv_rfData?.endereco?.numero}}<br>
                    {{colective?.rcv_rfData?.endereco?.bairro}} - {{colective?.rcv_rfData?.endereco?.municipio?.descricao}} - {{colective?.rcv_rfData?.endereco?.uf}}<br>
                    CEP: {{colective?.rcv_rfData?.endereco?.cep}}<br>
                    {{colective?.rcv_rfData?.endereco?.pais?.descricao}}
                </small>
            </div>
            <div v-if="colective?.rcv_rfData?.socios">
                <small v-for="(socio, index) in colective?.rcv_rfData?.socios" :key="index">
                <strong><?= i::__("Sócio:") ?></strong><br>
                    Nome: {{socio.nome}}
                    Tipo socio: {{socio.tipoSocio}}
                    CPF: {{socio.cpf}}
                    Quaificação: {{socio.qualificacao}}
                    Data inclusão: {{socio.dataInclusao}}
                    Pais: {{socio.descricao}}
                    Representante legal: {{socio.representanteLegal.nome}}, {{socio.representanteLegal.cpf}}, {{socio.representanteLegal.qualificacao}}
                </small>
            </div>
            <div v-if="colective?.rcv_rfData?.capitalSocial"><small><strong><?= i::__("Capital social:") ?></strong> {{colective?.rcv_rfData?.capitalSocial}}</small></div>
            <div v-if="colective?.rcv_rfData?.porte"><small><strong><?= i::__("Porte:") ?></strong> {{colective?.rcv_rfData?.porte}}</small></div>
            <div v-if="colective?.rcv_rfData?.cnaePrincipal">
                <small>
                    <strong><?= i::__("CNAE Principal:") ?></strong><br>
                    {{colective?.rcv_rfData?.cnaePrincipal?.descricao}}, {{colective?.rcv_rfData?.cnaePrincipal?.codigo}}
                </small>
            </div>
            <div v-if="colective?.rcv_rfData?.cnaeSecundarias">
                <small v-for="(cnae, index) in colective?.rcv_rfData?.cnaeSecundarias" :key="index">
                <strong><?= i::__("CNAE Secundárias:") ?></strong><br>
                    {{cnae.descricao}}, {{cnae.codigo}}
                </small>
            </div>
            <div v-if="colective?.rcv_rfData?.situacaoEspecial"><small><strong><?= i::__("Situação especial:") ?></strong> {{colective?.rcv_rfData?.situacaoEspecial}}</small></div>
            <div v-if="colective?.rcv_rfData?.dataSituacaoEspecial"><small><strong><?= i::__("Data situação especial:") ?></strong> {{colective?.rcv_rfData?.dataSituacaoEspecial}}</small></div>
            <div v-if="colective?.rcv_rfData?.informacoesAdicionais">
                <small>
                    <strong><?= i::__("Informações adicionais:") ?></strong><br>
                    Optante simples: {{colective?.rcv_rfData?.informacoesAdicionais?.optanteSimples}}
                    Optante Mei: {{colective?.rcv_rfData?.informacoesAdicionais?.optanteMei}}
                </small>
            </div>
        </div>
    </div>
</div>