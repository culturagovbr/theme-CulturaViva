# Componente `<rcv-countries>`
Componente para seleção de países

### Eventos
- **update:modelCountries** - disparado ao selecionar um ou mais países
  
## Propriedades
- *Array **modelCountries*** - Países selecionadas pelo componente


### Importando componente
```PHP
<?php 
$this->import('rcv-countries');
?>
```
### Exemplos de uso
```HTML
<!-- utilizaçao básica -->
<rcv-countries v-model:model-countries="paises"></rcv-countries>

```