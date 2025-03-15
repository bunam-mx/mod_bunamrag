/* eslint-disable no-unused-vars */
/* eslint-disable no-console */

define([], function() {
  const getUnits = (bunamragHub, ragcoursecode) => {
    var unitsURL = `${bunamragHub}/unidades/${ragcoursecode}`;

    // Realizar la petición fetch para obtener las unidades
    fetch(unitsURL)
        .then(response => response.json())
        .then(data => {
            // Manejar la respuesta de la petición
            console.log(data);
            // Obtener el select con el id 'dynamic_ragunitid'
            var select = document.getElementById('id_dynamic_ragunitid');

            // Limpiar las opciones actuales del select
            select.innerHTML = '<option value=""></option>';

            // Agregar las nuevas opciones al select
            data.forEach(item => {
                var option = document.createElement('option');
                option.value = item.id;
                option.text = item.name;
                select.appendChild(option);
            });

            // Agregar evento change al select
            select.addEventListener('change', function() {
              var selectedOption = select.options[select.selectedIndex];
              document.querySelector('input[name="ragunitid"]').value = selectedOption.value;
              document.querySelector('input[name="ragunit"]').value = selectedOption.text;
              getThemes(bunamragHub, selectedOption.value);
            });
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
  };

  const getThemes = (bunamragHub, ragunitid) => {
    var themesURL = `${bunamragHub}/temas/${ragunitid}`;
    fetch(themesURL)
        .then(response => response.json())
        .then(data => {
            // Manejar la respuesta de la petición
            console.log(data);
            // Obtener el select con el id 'dynamic_ragthemeid'
            var select = document.getElementById('id_dynamic_ragthemeid');

            // Limpiar las opciones actuales del select
            select.innerHTML = '<option value=""></option>';

            // Agregar las nuevas opciones al select
            data.forEach(item => {
                var option = document.createElement('option');
                option.value = item.id;
                option.text = item.name;
                select.appendChild(option);
            });

            // Agregar evento change al select
            select.addEventListener('change', function() {
              var selectedOption = select.options[select.selectedIndex];
              document.querySelector('input[name="ragthemeid"]').value = selectedOption.value;
              document.querySelector('input[name="ragtheme"]').value = selectedOption.text;
              getSubthemes(bunamragHub, selectedOption.value);
            });
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
  };

  const getSubthemes = (bunamragHub, ragthemeid) => {
    var subthemesURL = `${bunamragHub}/subtemas/${ragthemeid}`;
    fetch(subthemesURL)
        .then(response => response.json())
        .then(data => {
            // Manejar la respuesta de la petición
            console.log(data);

            // Obtener el select con el id 'dynamic_ragsubthemeid'
            var select = document.getElementById('id_dynamic_ragsubthemeid');

            // Limpiar las opciones actuales del select
            select.innerHTML = '<option value=""></option>';

            // Agregar las nuevas opciones al select
            data.forEach(item => {
              var option = document.createElement('option');
              option.value = item.id;
              option.text = item.name;
              select.appendChild(option);
          });

          // Agregar evento change al select
          select.addEventListener('change', function() {
            var selectedOption = select.options[select.selectedIndex];
            document.querySelector('input[name="ragsubthemeid"]').value = selectedOption.value;
            document.querySelector('input[name="ragsubtheme"]').value = selectedOption.text;
          });
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
  };

  return {
      init: function() {
        const bunamragHub = "http://localhost:3333";
        const ragcoursecode = document.querySelector('input[name="ragcoursecode"]').value;

        getUnits(bunamragHub, ragcoursecode);
      }
  };
});