<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-confirm-button
    mc-loading
');
?>

<div class="rcv-registration-trash">
    <div class="rcv-registration-trash__tabs" style="display: flex; gap: 8px; margin-bottom: 24px;">
        <button class="button" :class="aba === 'enviar' ? 'button--primary' : 'button--primary-outline'" @click="mudarAba('enviar')"><?= i::__('Enviar para a lixeira') ?></button>
        <button class="button" :class="aba === 'lixeira' ? 'button--primary' : 'button--primary-outline'" @click="mudarAba('lixeira')"><?= i::__('Na lixeira') ?></button>
    </div>

    <mc-loading :condition="carregando"><?= i::__('Processando') ?></mc-loading>

    <!-- envio -->
    <div v-if="aba === 'enviar'" v-show="!carregando">
        <div class="field">
            <label for="rcv-lixeira-numeros"><?= i::__('Números das inscrições') ?></label>
            <textarea id="rcv-lixeira-numeros" v-model="numeros" rows="5" placeholder="on-123456; on-789012"></textarea>
            <small><?= i::__('Separe por ponto e vírgula, vírgula, espaço ou quebra de linha. O prefixo on- é opcional. Até 200 por vez.') ?></small>
        </div>
        <button class="button button--primary" :disabled="!numeros.trim()" @click="analisar()" style="margin-top: 12px;"><?= i::__('Analisar') ?></button>

        <div v-if="analise" style="margin-top: 24px;">
            <p v-if="analise.invalidos.length"><strong><?= i::__('Não reconhecidos:') ?></strong> {{ analise.invalidos.join(', ') }}</p>

            <table class="rcv-registration-trash__table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #ccc;">
                        <th><?= i::__('Inscrição') ?></th>
                        <th><?= i::__('Organização') ?></th>
                        <th><?= i::__('Status') ?></th>
                        <th><?= i::__('Fases e avaliações') ?></th>
                        <th><?= i::__('Situação') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in analise.itens" :key="item.numero" style="border-bottom: 1px solid #eee; vertical-align: top;">
                        <td>
                            <a v-if="item.id" :href="urlInscricao(item.id)" target="_blank">{{ item.numero }}</a>
                            <span v-else>{{ item.numero }}</span>
                            <div v-if="item.categoria"><small>{{ item.categoria }}</small></div>
                            <div v-if="item.responsavel"><small>{{ item.responsavel }}</small></div>
                        </td>
                        <td>
                            <span v-if="item.organizacao">{{ item.organizacao.nome }}<br><small>{{ item.organizacao.cnpj }}</small></span>
                            <span v-else>-</span>
                        </td>
                        <td>{{ item.status_nome || '-' }}</td>
                        <td>
                            <div v-for="fase in item.fases" :key="fase.id"><small>#{{ fase.id }} ({{ fase.oportunidade }}): {{ avaliacoes(fase) }}</small></div>
                        </td>
                        <td>
                            <strong :style="{ color: item.situacao === 'bloqueada' ? '#b00020' : (item.situacao === 'aviso' ? '#a15c00' : '#1b7a3a') }">{{ text(item.situacao) }}</strong>
                            <div><small>{{ motivos(item) }}</small></div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="enviaveis.length" class="field" style="margin-top: 24px;">
                <label for="rcv-lixeira-motivo"><?= i::__('Motivo (obrigatório, mínimo de 10 caracteres)') ?></label>
                <textarea id="rcv-lixeira-motivo" v-model="motivo" rows="3"></textarea>
            </div>

            <mc-confirm-button v-if="enviaveis.length" @confirm="enviar()" title="<?= i::esc_attr__('Enviar para a lixeira') ?>" yes="<?= i::esc_attr__('Enviar') ?>" no="<?= i::esc_attr__('Cancelar') ?>">
                <template #button="{ open }">
                    <button class="button button--primary" :disabled="!motivoValido" @click="open()" style="margin-top: 12px;">
                        <?= i::__('Enviar') ?> {{ enviaveis.length }} <?= i::__('inscrições para a lixeira') ?>
                    </button>
                </template>
                <template #message>
                    <?= i::__('As inscrições saem das filas dos avaliadores e das listas. As avaliações iniciadas e concluídas são guardadas para a restauração. Confirma?') ?>
                </template>
            </mc-confirm-button>
            <p v-else><?= i::__('Nenhuma inscrição da lista pode ser enviada para a lixeira.') ?></p>
        </div>
    </div>

    <!-- lixeira -->
    <div v-if="aba === 'lixeira'" v-show="!carregando">
        <p v-if="!lista.length"><?= i::__('Nenhuma inscrição na lixeira.') ?></p>

        <template v-else>
            <table class="rcv-registration-trash__table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #ccc;">
                        <th><input type="checkbox" :checked="todosSelecionados" :disabled="!restauraveis.length" @change="alternarTodos()"></th>
                        <th><?= i::__('Inscrição') ?></th>
                        <th><?= i::__('Organização') ?></th>
                        <th><?= i::__('Status anterior') ?></th>
                        <th><?= i::__('Enviada por') ?></th>
                        <th><?= i::__('Motivo') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in lista" :key="item.numero" style="border-bottom: 1px solid #eee; vertical-align: top;">
                        <td><input type="checkbox" :value="item.numero" v-model="selecionados" :disabled="!item.restauravel"></td>
                        <td>
                            <a :href="urlInscricao(item.id)" target="_blank">{{ item.numero }}</a>
                            <div><small>{{ item.categoria }}</small></div>
                        </td>
                        <td>{{ item.organizacao || '-' }}</td>
                        <td>{{ item.status_anterior || '-' }}</td>
                        <td>
                            <span v-if="item.enviada_por">{{ item.enviada_por }}<br><small>{{ item.enviada_em }}</small></span>
                            <small v-else>{{ text('sem_backup') }}</small>
                        </td>
                        <td>{{ item.motivo || '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <mc-confirm-button @confirm="restaurar()" title="<?= i::esc_attr__('Restaurar inscrições') ?>" yes="<?= i::esc_attr__('Restaurar') ?>" no="<?= i::esc_attr__('Cancelar') ?>">
                <template #button="{ open }">
                    <button class="button button--primary" :disabled="!selecionados.length" @click="open()" style="margin-top: 12px;">
                        <?= i::__('Restaurar') ?> {{ selecionados.length }} <?= i::__('inscrições') ?>
                    </button>
                </template>
                <template #message>
                    <?= i::__('As inscrições voltam ao status anterior, com os avaliadores e as avaliações guardadas. Confirma?') ?>
                </template>
            </mc-confirm-button>
        </template>
    </div>

    <!-- resultado da última operação -->
    <div v-if="resultado" style="margin-top: 24px;">
        <h4><?= i::__('Resultado') ?></h4>
        <ul>
            <li v-for="item in resultado" :key="item.numero">
                <strong>{{ item.numero }}</strong>: {{ text(item.resultado) }}<span v-if="item.motivos?.length"> — {{ motivos(item) }}</span>
            </li>
        </ul>
    </div>
</div>
