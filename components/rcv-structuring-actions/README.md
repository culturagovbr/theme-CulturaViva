# Componente `<rcv-structuring-actions>`
Componente para seleção das ações estruturantes no tema do RCV. Utilizado nos filtros de busca.

### Eventos
- **update:modelActions** - disparado quando alguma ação é selecionada, atualizando a prop modelActions
- **update:modelOtherActions** - disparado quando alguma outra ação é selecionada, atualizando a prop modelOtherActions
  
## Propriedades
- *Array **modelActions*** - Ações estruturantes
- *Array **modelActions*** - Outras ações estruturantes

> Os valores vêm com as vírgulas escapadas (`$RCV.taxonomyFilterItems`). Para exibir o rótulo, use os
> dicionários `actions` / `otherActions` como `labels`.

### Importando componente
```PHP
<?php 
$this->import('rcv-structuring-actions');
?>
```
### Exemplos de uso
```HTML
<!-- utilizaçao básica -->
<rcv-structuring-actions v-model:model-actions="actions" v-model:model-other-actions="otherActions"></rcv-structuring-actions>

```