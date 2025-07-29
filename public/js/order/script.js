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
                option.value = area.Ref;
                option.textContent = area.Description;
                regionSelect.appendChild(option);
            });
        });

    regionSelect.addEventListener('change', function () {
        citySelect.innerHTML = '<option value="">Оберіть місто</option>';
        warehouseSelect.innerHTML = '<option value="">Оберіть відділення</option>';
        citySelect.disabled = true;
        warehouseSelect.disabled = true;

        document.getElementById('region-name-input').value = regionSelect.options[regionSelect.selectedIndex].textContent;

        const selectedRegion = regionSelect.value;
        if (!selectedRegion) return;

        fetch('https://api.novaposhta.ua/v2.0/json/', {
            method: 'POST',
            body: JSON.stringify({
                apiKey: API_KEY,
                modelName: 'Address',
                calledMethod: 'getCities',
                methodProperties: {
                    AreaRef: selectedRegion
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

        document.getElementById('city-name-input').value = citySelect.options[citySelect.selectedIndex].textContent;

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

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('item-modal');
    const closeBtn = modal.querySelector('.close');
    const itemFieldsContainer = document.getElementById('item-fields-container');
    const form = document.getElementById('item-edit-form');
    let selectedIndex = null;

    document.querySelectorAll('.cart-item').forEach((item, index) => {
        item.addEventListener('click', () => {
            selectedIndex = index;
            const quantity = parseInt(item.querySelector('.summary-details div').textContent.match(/x(\d+)/)[1]);
            const productName = item.dataset.name;
            const productImage = item.dataset.image;
            const flavors = JSON.parse(item.dataset.categories);
            itemFieldsContainer.innerHTML = '';

            const saved = JSON.parse(sessionStorage.getItem('itemDetails') || '{}');
            const existingData = saved[selectedIndex] || [];

            for (let i = 0; i < quantity; i++) {
                const savedItem = existingData[i] || {};
                const block = document.createElement('div');
                block.className = 'modal-item';

                block.innerHTML = `
                    <img src="${productImage}" alt="${productName}" class="product-image">
                    <h4 class="product-name">${productName}</h4>

                    <label for="photo_${i}" class="custom-file-upload">Завантажити фото</label>
                    <input type="file" id="photo_${i}" name="photo_${i}">
                    <div class="file-info" id="file-info-${i}">
                        ${savedItem.photoName ? `📎 ${savedItem.photoName}` : 'Файл не вибрано'}
                    </div>

                    <textarea name="message_${i}" placeholder="Ваш текст на шоколадку"
                        rows="1" class="auto-resize">${savedItem.message || ''}</textarea>

                    <select name="category_${i}" required>
                        <option value="">Оберіть смак</option>
                        ${flavors.map(flavor => `
                            <option value="${flavor.id}" ${savedItem.categoryId == flavor.id ? 'selected' : ''}>
                                ${flavor.name}
                            </option>
                        `).join('')}
                    </select>
                `;

                itemFieldsContainer.appendChild(block);

                const fileInput = block.querySelector(`#photo_${i}`);
                const fileInfo = block.querySelector(`#file-info-${i}`);
                fileInput.addEventListener('change', () => {
                    const file = fileInput.files[0];
                    fileInfo.innerHTML = file
                        ? `📎 ${file.name}`
                        : 'Файл не вибрано';
                });
            }

            modal.classList.remove('hidden');
            modal.style.display = 'block';
        });
    });

    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    document.getElementById('checkout-form').addEventListener('submit', function (e) {
        const itemDetails = sessionStorage.getItem('itemDetails');
        document.getElementById('item-details-input').value = itemDetails || '{}';
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const itemData = [];

        const blocks = itemFieldsContainer.querySelectorAll('.modal-item');
        for (let i = 0; i < blocks.length; i++) {
            const message = blocks[i].querySelector(`textarea[name="message_${i}"]`).value;
            const fileInput = blocks[i].querySelector(`input[name="photo_${i}"]`);
            const file = fileInput.files[0];

            const categorySelect = blocks[i].querySelector(`select[name="category_${i}"]`);
            const categoryId = categorySelect ? categorySelect.value : null;

            let photoBase64 = null;
            let fileName = null;

            if (file) {
                photoBase64 = await readFileAsDataURL(file);
            }

            itemData.push({
                message,
                photo: photoBase64,
                photoName: file ? file.name : null,
                categoryId,
            });
        }

        const saved = JSON.parse(sessionStorage.getItem('itemDetails') || '{}');
        saved[selectedIndex] = itemData;
        sessionStorage.setItem('itemDetails', JSON.stringify(saved));

        modal.style.display = 'none';
    });

    function readFileAsDataURL(file) {
        return new Promise(resolve => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.readAsDataURL(file);
        });
    }
});
