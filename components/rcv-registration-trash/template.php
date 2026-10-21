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
                                <template #default="modal">
                                    <p>{{ formatar('enviarConfirmacao', enviaveis.length) }}</p>
                                    <div class="field rcv-registration-trash__senha">
                                        <label for="rcv-lixeira-senha-enviar"><?= i::__('Sua senha') ?></label>
                                        <input id="rcv-lixeira-senha-enviar" type="password" v-model="senha" autocomplete="current-password" @keydown.enter.prevent="enviar(modal)">
                                        <small class="rcv-registration-trash__hint">{{ text('dicaSenha') }}</small>
                                    </div>
                                </template>

                                <template #actions="modal">
                                    <button class="button button--text button--md" @click="modal.close()"><?= i::__('Cancelar') ?></button>
                                    <button class="button button--primary button--md" :disabled="carregando || !senha" @click="enviar(modal)"><?= i::__('Confirmar') ?></button>
                                </template>

                                <template #button="modal">
                                    <button type="button" class="button button--primary button--md" :disabled="!motivoValido" @click="abrir(modal)">
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

                    <form class="rcv-registration-trash__filtros" @submit.prevent>
                        <div class="field">
                            <label for="rcv-lixeira-busca"><?= i::__('Buscar') ?></label>
                            <textarea id="rcv-lixeira-busca" v-model="busca" rows="3" :placeholder="text('buscarPlaceholder')" @input="buscar()"></textarea>
                            <small class="rcv-registration-trash__hint">{{ text('dicaBusca') }}</small>
                        </div>
                    </form>

                    <mc-alert type="warning" v-if="foraDaLixeira.length">
                        {{ formatar('foraDaLixeira', foraDaLixeira.length, numerosBuscados, foraDaLixeira.join(', ')) }}
                    </mc-alert>

                    <div class="rcv-registration-trash__pilulas" role="group" :aria-label="text('filtrar')">
                        <button
                            v-for="opcao in filtros"
                            :key="opcao"
                            type="button"
                            class="rcv-registration-trash__pilula"
                            :class="{'rcv-registration-trash__pilula--ativa': filtro === opcao}"
                            :aria-pressed="filtro === opcao"
                            @click="escolherFiltro(opcao)">
                            {{ text('filtro_' + opcao) }} <strong>{{ totais[opcao] || 0 }}</strong>
                        </button>
                    </div>

                    <!-- lote: todas as restauráveis encontradas pela busca, além da página -->
                    <div class="rcv-registration-trash__lote" v-if="loteDisponivel">
                        <span v-if="numerosBuscados">{{ formatar('loteResumoLista', totais.restauraveis, numerosBuscados) }}</span>
                        <span v-else>{{ formatar('loteResumo', totais.restauraveis) }}</span>

                        <mc-modal classes="rcv-registration-trash__modal" :title="text('loteTitulo')">
                            <template #default="modal">
                                <p v-if="numerosBuscados">{{ formatar('loteConfirmacaoLista', loteTamanho, numerosBuscados) }}</p>
                                <p v-else>{{ formatar('loteConfirmacao', loteTamanho, busca) }}</p>
                                <p class="rcv-registration-trash__hint" v-if="totais.restauraveis > limite">{{ formatar('loteTeto', limite, totais.restauraveis) }}</p>
                                <div class="field rcv-registration-trash__senha">
                                    <label for="rcv-lixeira-senha-lote"><?= i::__('Sua senha') ?></label>
                                    <input id="rcv-lixeira-senha-lote" type="password" v-model="senha" autocomplete="current-password" @keydown.enter.prevent="restaurarBusca(modal)">
                                    <small class="rcv-registration-trash__hint">{{ text('dicaSenha') }}</small>
                                </div>
                            </template>

                            <template #actions="modal">
                                <button class="button button--text button--md" @click="modal.close()"><?= i::__('Cancelar') ?></button>
                                <button class="button button--primary button--md" :disabled="carregando || !senha" @click="restaurarBusca(modal)"><?= i::__('Confirmar') ?></button>
                            </template>

                            <template #button="modal">
                                <button type="button" class="button button--primary-outline button--sm" @click="abrir(modal)">
                                    <mc-icon name="history"></mc-icon>
                                    {{ formatar('loteBotao', loteTamanho) }}
                                </button>
                            </template>
                        </mc-modal>
                    </div>

                    <div class="rcv-registration-trash__acoes" v-if="lista.length || selecionados.length">
                        <label class="rcv-registration-trash__selecionar-todas">
                            <input type="checkbox" :checked="carregadasSelecionadas" :disabled="!restauraveis.length" @change="alternarCarregadas()">
                            {{ text('selecionarCarregadas') }}
                        </label>

                        <div class="rcv-registration-trash__resumo">
                            <span class="rcv-registration-trash__contador">{{ formatar('selecionadas', selecionados.length) }}</span>
                            <span class="rcv-registration-trash__limite" v-if="acimaDoLimite">{{ formatar('acimaDoLimite', limite) }}</span>

                            <button type="button" class="button button--text button--sm" v-if="selecionados.length" @click="limparSelecao()">
                                {{ text('limparSelecao') }}
                            </button>

                            <mc-modal classes="rcv-registration-trash__modal" :title="text('restaurarTitulo')">
                                <template #default="modal">
                                    <p>{{ formatar('restaurarConfirmacao', selecionados.length) }}</p>
                                    <div class="field rcv-registration-trash__senha">
                                        <label for="rcv-lixeira-senha-restaurar"><?= i::__('Sua senha') ?></label>
                                        <input id="rcv-lixeira-senha-restaurar" type="password" v-model="senha" autocomplete="current-password" @keydown.enter.prevent="restaurar(modal)">
                                        <small class="rcv-registration-trash__hint">{{ text('dicaSenha') }}</small>
                                    </div>
                                </template>

                                <template #actions="modal">
                                    <button class="button button--text button--md" @click="modal.close()"><?= i::__('Cancelar') ?></button>
                                    <button class="button button--primary button--md" :disabled="carregando || !senha" @click="restaurar(modal)"><?= i::__('Confirmar') ?></button>
                                </template>

                                <template #button="modal">
                                    <button type="button" class="button button--primary button--sm" :disabled="!selecionados.length || acimaDoLimite" @click="abrir(modal)">
                                        <mc-icon name="history"></mc-icon>
                                        {{ formatar('restaurarBotao', selecionados.length) }}
                                    </button>
                                </template>
                            </mc-modal>
                        </div>
                    </div>

                    <mc-loading :condition="carregando && !lista.length"></mc-loading>

                    <div class="rcv-registration-trash__vazio" v-if="!lista.length && !carregando">
                        <mc-icon name="trash"></mc-icon>

                        <template v-if="filtroAtivo">
                            <p>{{ text('nadaEncontrado') }}</p>
                            <button type="button" class="button button--primary-outline button--sm" @click="limparFiltros()">{{ text('limparFiltros') }}</button>
                        </template>

                        <p v-else>{{ text('lixeiraVazia') }}</p>
                    </div>

                    <ul class="rcv-registration-trash__lista" v-if="lista.length">
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

                    <div class="rcv-registration-trash__mais" v-if="lista.length">
                        <span class="rcv-registration-trash__hint">{{ formatar('exibindo', lista.length, total) }}</span>

                        <button type="button" class="button button--large button--primary-outline" v-if="pagina < paginas" :disabled="carregando" @click="carregarMais()">
                            <?= i::__('Carregar mais') ?>
                        </button>
                    </div>
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
