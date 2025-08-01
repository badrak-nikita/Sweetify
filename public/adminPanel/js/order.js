document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".change-status-btn").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            const dropdown = btn.nextElementSibling;
            document.querySelectorAll(".status-dropdown").forEach(menu => {
                if (menu !== dropdown) menu.classList.add("d-none");
            });
            dropdown.classList.toggle("d-none");
        });
    });

    document.querySelectorAll(".status-dropdown .dropdown-item").forEach((item) => {
        item.addEventListener("click", (e) => {
            e.preventDefault();
            const newStatus = item.dataset.status;
            const tr = item.closest("tr");
            const orderId = tr.dataset.id;

            fetch(`/admin/orders/${orderId}/status`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || ""
                },
                body: JSON.stringify({ status: newStatus }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        // Обновить текст и класс бейджа
                        const statusCell = tr.querySelector("td:nth-child(4)");
                        const badge = statusCell.querySelector(".badge");

                        const statuses = {
                            1: "Очікує",
                            2: "Завершено",
                            3: "Доставляється",
                            4: "Скасовано",
                        };

                        const badgeClasses = {
                            1: "info",
                            2: "success",
                            3: "warning",
                            4: "danger",
                        };

                        badge.className = `badge badge-${badgeClasses[newStatus]}`;
                        badge.textContent = statuses[newStatus];
                    } else {
                        alert("Не вдалося оновити статус.");
                    }
                })
                .catch(() => {
                    alert("Сталася помилка при відправці запиту.");
                });

            // Скрыть меню
            item.closest(".status-dropdown").classList.add("d-none");
        });
    });

    // Кнопка "Перегляд деталей"
    document.querySelectorAll(".view-details-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const tr = btn.closest("tr");
            const data = JSON.parse(tr.dataset.order);

            document.querySelectorAll("table tbody tr").forEach(row => {
                row.classList.remove("highlighted-row");
            });

            tr.classList.add("highlighted-row");

            document.getElementById("detail-name").textContent = data.name || "-";
            document.getElementById("detail-email").textContent = data.email || "-";
            document.getElementById("detail-phone").textContent = data.phone || "-";
            document.getElementById("detail-region").textContent = data.region || "-";
            document.getElementById("detail-city").textContent = data.city || "-";
            document.getElementById("detail-department").textContent = data.department || "-";
            document.getElementById("detail-comment").textContent = data.comment || "-";

            const itemsBody = document.getElementById("detail-items");
            itemsBody.innerHTML = "";

            if (data.items && data.items.length > 0) {
                data.items.forEach(item => {
                    const name = item.product ?? "-";
                    const price = item.price ?? "-";
                    const category = item.category ?? "-";
                    const message = item.messageText ?? "-";
                    const image = item.imagePath
                        ? `<img src="${item.imagePath}" alt="Зображення" width="50">`
                        : "-";

                    const row = document.createElement("tr");
                    row.innerHTML = `
                    <td data-label="Назва">${name}</td>
                    <td data-label="Цiна">${price} грн</td>
                    <td data-label="Смак">${category}</td>
                    <td data-label="Текст">${message}</td>
                    <td>${image}</td>
                `;
                    itemsBody.appendChild(row);
                });
            } else {
                itemsBody.innerHTML = '<tr><td colspan="5" class="text-center">Немає товарів</td></tr>';
            }
        });
    });

});
