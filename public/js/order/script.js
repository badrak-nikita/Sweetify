function selectOnlyOne(checkbox) {
    const checkboxes = document.querySelectorAll('input[name="paymentTypeId"]');
    checkboxes.forEach(cb => {
        if (cb !== checkbox) cb.checked = false;
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const API_KEY = '7ce71648522fb45f66f02e117c55d83d';

    const regionSelect = document.getElementById('region-select');
    const citySelect = document.getElementById('city-select');
    const warehouseSelect = document.getElementById('warehouse-select');

    fetch('https://api.novaposhta.ua/v2.0/json/', {
        method: 'POST',
        body: JSON.stringify({
            apiKey: API_KEY,
            modelName: 'Address',
            calledMethod: 'getAreas',
            methodProperties: {}
        }),
        headers: { 'Content-Type': 'application/json' }
    })
        .then(res => res.json())
        .then(data => {
            data.data.forEach(area => {
                const option = document.createElement('option');
                option.value = area.Description;
                option.textContent = area.Description;
                regionSelect.appendChild(option);
            });
        });

    regionSelect.addEventListener('change', function () {
        citySelect.innerHTML = '<option value="">Оберіть місто</option>';
        warehouseSelect.innerHTML = '<option value="">Оберіть відділення</option>';
        citySelect.disabled = true;
        warehouseSelect.disabled = true;

        const selectedRegion = regionSelect.value;
        if (!selectedRegion) return;

        fetch('https://api.novaposhta.ua/v2.0/json/', {
            method: 'POST',
            body: JSON.stringify({
                apiKey: API_KEY,
                modelName: 'Address',
                calledMethod: 'getCities',
                methodProperties: {
                    Area: selectedRegion
                }
            }),
            headers: { 'Content-Type': 'application/json' }
        })
            .then(res => res.json())
            .then(data => {
                data.data.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.Ref;
                    option.textContent = city.Description;
                    option.setAttribute('data-full', JSON.stringify(city));
                    citySelect.appendChild(option);
                });
                citySelect.disabled = false;
            });
    });

    citySelect.addEventListener('change', function () {
        warehouseSelect.innerHTML = '<option value="">Оберіть відділення</option>';
        warehouseSelect.disabled = true;

        const cityRef = citySelect.value;
        if (!cityRef) return;

        fetch('https://api.novaposhta.ua/v2.0/json/', {
            method: 'POST',
            body: JSON.stringify({
                apiKey: API_KEY,
                modelName: 'AddressGeneral',
                calledMethod: 'getWarehouses',
                methodProperties: {
                    CityRef: cityRef
                }
            }),
            headers: { 'Content-Type': 'application/json' }
        })
            .then(res => res.json())
            .then(data => {
                data.data.forEach(warehouse => {
                    const option = document.createElement('option');
                    option.value = warehouse.Description;
                    option.textContent = warehouse.Description;
                    warehouseSelect.appendChild(option);
                });
                warehouseSelect.disabled = false;
            });
    });
});
