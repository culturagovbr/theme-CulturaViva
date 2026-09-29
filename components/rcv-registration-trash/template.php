<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-alert
    mc-card
    mc-icon
    mc-loading
    mc-modal
    mc-tab
    mc-tabs
');
?>
<div class="rcv-registration-trash">
    <mc-tabs class="rcv-registration-trash__tabs" @changed="mudarAba($event.tab.slug)">
        <mc-tab label="<?php i::esc_attr_e('Enviar para a lixeira') ?>" slug="enviar">
            <mc-card>
                <template #title>
                    <h3><?= i::__('Números das inscrições') ?></h3>
                </template>

                <template #content>
                    <p class="rcv-registration-trash__lead">{{ text('explicacaoEnvio') }}</p>

                    <div class="field">
                        <label for="rcv-lixeira-numeros"><?= i::__('Números') ?></label>
                        <textarea id="rcv-lixeira-numeros" v-model="numeros" rows="4" placeholder="on-123456; on-789012"></textarea>
                        <small class="rcv-registration-trash__hint">{{ text('dicaNumeros') }}</small>
                    </div>

                    <div class="rcv-registration-trash__barra">
                        <button type="button" class="button button--primary button--md" :disabled="!numeros.trim() || carregando" @click="analisar()">
                            <mc-icon name="search"></mc-icon>
                            <?= i::__('Analisar') ?>
                        </button>
                    </div>
                </template>
            </mc-card>

            <mc-loading :condition="carregando"></mc-loading>

            <mc-alert type="warning" v-if="analise?.invalidos.length">
                {{ formatar('naoReconhecidos', analise.invalidos.join(', ')) }}
            </mc-alert>

            <mc-card v-if="analise && !carregando">
                <template #title>
                    <h3><?= i::__('Análise') ?></h3>
                </template>

                <template #content>
                    <div class="rcv-registration-trash__contagem">
                        <span v-for="situacao in situacoes" :key="situacao" class="rcv-registration-trash__pilula">
                            {{ text(situacao) }} <strong>{{ contagem[situacao] }}</strong>
                        </span>
                    </div>

                    <ul class="rcv-registration-trash__lista">
                        <li v-for="item in analise.itens" :key="item.numero" class="rcv-trash-item">
                            <div class="rcv-trash-item__linha">
                                <div class="rcv-trash-item__info">
                                    <h4 class="rcv-trash-item__numero">
                                        <a v-if="item.id" :href="urlInscricao(item.id)" target="_blank">{{ item.numero }}</a>
                                        <span v-else>{{ item.numero }}</span>
                                    </h4>

                                    <p class="rcv-trash-item__meta" v-if="item.id">
                                        <span v-if="item.organizacao">{{ item.organizacao.nome }} <small>{{ item.organizacao.cnpj }}</small></span>
                                        <span>{{ item.categoria }}</span>
                                        <span v-if="item.responsavel">{{ formatar('responsavel', item.responsavel) }}</span>
                                        <span>{{ formatar('statusAtual', item.status_nome) }}</span>
                                    </p>

                                    <p class="rcv-trash-item__fases" v-if="item.fases?.length">
                                        <span v-for="fase in item.fases" :key="fase.id">{{ formatar('fase', fase.oportunidade, avaliacoes(fase)) }}</span>
                                    </p>

                                    <p class="rcv-trash-item__motivo" v-if="item.motivos.length">{{ motivos(item) }}</p>
                                </div>

                                <div class="rcv-trash-item__situacao">
                                    <span class="mc-status" :class="'mc-status--' + tom(item.situacao)">
                                        <mc-icon name="dot"></mc-icon>
                                        <span>{{ text(item.situacao) }}</span>
                                    </span>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <template v-if="enviaveis.length">
                        <div class="field rcv-registration-trash__motivo">
                            <label for="rcv-lixeira-motivo"><?= i::__('Motivo') ?></label>
                            <textarea id="rcv-lixeira-motivo" v-model="motivo" rows="3"></textarea>
                            <small class="rcv-registration-trash__hint">{{ text('dicaMotivo') }}</small>
                        </div>

                        <div class="rcv-registration-trash__barra">
                            <mc-modal classes="rcv-registration-trash__modal" :title="text('enviarTitulo')">
                                <template #default>
                                    <p>{{ formatar('enviarConfirmacao', enviaveis.length) }}</p>
                                </template>

                                <template #actions="modal">
                                    <button class="button button--text button--md" @click="modal.close()"><?= i::__('Cancelar') ?></button>
                                    <button class="button button--primary button--md" :disabled="carregando" @click="enviar(modal)"><?= i::__('Confirmar') ?></button>
                                </template>

                                <template #button="modal">
                                    <button type="button" class="button button--primary button--md" :disabled="!motivoValido" @click="modal.open()">
                                        <mc-icon name="trash"></mc-icon>
                                        {{ formatar('enviarBotao', enviaveis.length) }}
                                    </button>
                                </template>
                            </mc-modal>
                        </div>
                    </template>

                    <mc-alert type="helper" v-else>{{ text('nadaParaEnviar') }}</mc-alert>
                </template>
            </mc-card>
        </mc-tab>

        <mc-tab label="<?php i::esc_attr_e('Na lixeira') ?>" slug="lixeira">
            <mc-card>
                <template #title>
                    <h3><?= i::__('Inscrições na lixeira') ?></h3>
                </template>

                <template #content>
                    <p class="rcv-registration-trash__lead">{{ text('explicacaoLixeira') }}</p>

                    <mc-loading :condition="carregando"></mc-loading>

                    <div class="rcv-registration-trash__vazio" v-if="!lista.length && !carregando">
                        <mc-icon name="trash"></mc-icon>
                        <p>{{ text('lixeiraVazia') }}</p>
                    </div>

                    <template v-if="lista.length && !carregando">
                        <div class="rcv-registration-trash__acoes">
                            <label class="rcv-registration-trash__selecionar-todas">
                                <input type="checkbox" :checked="todosSelecionados" :disabled="!restauraveis.length" @change="alternarTodos()">
                                {{ text('selecionarTodas') }}
                            </label>

                            <div class="rcv-registration-trash__resumo">
                                <span class="rcv-registration-trash__contador">{{ formatar('selecionadas', selecionados.length) }}</span>

                                <mc-modal classes="rcv-registration-trash__modal" :title="text('restaurarTitulo')">
                                    <template #default>
                                        <p>{{ formatar('restaurarConfirmacao', selecionados.length) }}</p>
                                    </template>

                                    <template #actions="modal">
                                        <button class="button button--text button--md" @click="modal.close()"><?= i::__('Cancelar') ?></button>
                                        <button class="button button--primary button--md" :disabled="carregando" @click="restaurar(modal)"><?= i::__('Confirmar') ?></button>
                                    </template>

                                    <template #button="modal">
                                        <button type="button" class="button button--primary button--sm" :disabled="!selecionados.length" @click="modal.open()">
                                            <mc-icon name="history"></mc-icon>
                                            {{ formatar('restaurarBotao', selecionados.length) }}
                                        </button>
                                    </template>
                                </mc-modal>
                            </div>
                        </div>

                        <ul class="rcv-registration-trash__lista">
                            <li v-for="item in lista" :key="item.numero" class="rcv-trash-item" :class="{'rcv-trash-item--bloqueado': !item.restauravel}">
                                <div class="rcv-trash-item__linha">
                                    <label class="rcv-trash-item__selecao">
                                        <input type="checkbox" :value="item.numero" v-model="selecionados" :disabled="!item.restauravel" :aria-label="formatar('selecionar', item.numero)">
                                    </label>

                                    <div class="rcv-trash-item__info">
                                        <h4 class="rcv-trash-item__numero">
                                            <a :href="urlInscricao(item.id)" target="_blank">{{ item.numero }}</a>
                                        </h4>

                                        <p class="rcv-trash-item__meta">
                                            <span v-if="item.organizacao">{{ item.organizacao }}</span>
                                            <span>{{ item.categoria }}</span>
                                            <span v-if="item.status_anterior">{{ formatar('statusAnterior', item.status_anterior) }}</span>
                                        </p>

                                        <p class="rcv-trash-item__motivo" v-if="item.motivo">{{ item.motivo }}</p>
                                        <p class="rcv-trash-item__motivo rcv-registration-trash__muted" v-if="!item.restauravel">{{ text('sem_backup') }}</p>
                                    </div>

                                    <div class="rcv-trash-item__situacao" v-if="item.enviada_por">
                                        <span>{{ item.enviada_por }}</span>
                                        <small>{{ quando(item.enviada_em) }}</small>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </template>
                </template>
            </mc-card>
        </mc-tab>
    </mc-tabs>

    <mc-card v-if="resultado">
        <template #title>
            <h3><?= i::__('Resultado') ?></h3>
        </template>

        <template #content>
            <ul class="rcv-registration-trash__lista">
                <li v-for="item in resultado" :key="item.numero" class="rcv-trash-item">
                    <div class="rcv-trash-item__linha">
                        <div class="rcv-trash-item__info">
                            <h4 class="rcv-trash-item__numero">{{ item.numero }}</h4>
                            <p class="rcv-trash-item__motivo" v-if="item.motivos?.length">{{ motivos(item) }}</p>
                        </div>

                        <div class="rcv-trash-item__situacao">
                            <span class="mc-status" :class="'mc-status--' + tom(item.resultado)">
                                <mc-icon name="dot"></mc-icon>
                                <span>{{ text(item.resultado) }}</span>
                            </span>
                        </div>
                    </div>
                </li>
            </ul>
        </template>
    </mc-card>
</div>
