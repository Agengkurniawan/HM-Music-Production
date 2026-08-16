import "./bootstrap";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";

// Customer mobile navigation
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.querySelector("[data-customer-sidebar]");
    const toggle = document.querySelector("[data-customer-nav-toggle]");
    const close = document.querySelector("[data-customer-nav-close]");
    const backdrop = document.querySelector("[data-customer-nav-backdrop]");

    if (!sidebar || !toggle || !backdrop) return;

    const setOpen = (isOpen) => {
        sidebar.classList.toggle("is-open", isOpen);
        backdrop.classList.toggle("is-open", isOpen);
        document.body.classList.toggle("customer-nav-open", isOpen);
        toggle.setAttribute("aria-expanded", String(isOpen));
        sidebar.setAttribute("aria-hidden", String(!isOpen && window.innerWidth <= 900));
    };

    toggle.addEventListener("click", () => setOpen(!sidebar.classList.contains("is-open")));
    close?.addEventListener("click", () => setOpen(false));
    backdrop.addEventListener("click", () => setOpen(false));
    sidebar.querySelectorAll("a").forEach((link) => link.addEventListener("click", () => {
        if (window.innerWidth <= 900) setOpen(false);
    }));
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") setOpen(false);
    });
    window.addEventListener("resize", () => {
        if (window.innerWidth > 900) setOpen(false);
        else sidebar.setAttribute("aria-hidden", String(!sidebar.classList.contains("is-open")));
    });

    sidebar.setAttribute("aria-hidden", String(window.innerWidth <= 900));
});

// Notifikasi
document.addEventListener("DOMContentLoaded", function () {
    const notificationButtons = document.querySelectorAll(
        "[data-notification-toggle], #openNotification",
    );

    notificationButtons.forEach((openNotification) => {
        const panelId =
            openNotification.getAttribute("aria-controls") ||
            openNotification.dataset.notificationTarget ||
            "notificationPanel";
        const notificationPanel = document.getElementById(panelId);
        const closeNotification = notificationPanel?.querySelector(
            "[data-notification-close], #closeNotification",
        );
        const readUrl = notificationPanel?.dataset.notificationReadUrl;
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]',
        )?.content;

        if (!notificationPanel) return;

        const formatNotificationCount = (count) =>
            count > 9 ? "9+" : String(count);

        const updateNotificationBadge = (nextCount) => {
            const count = Math.max(Number.parseInt(nextCount, 10) || 0, 0);
            let badge = openNotification.querySelector(
                "[data-notification-badge]",
            );

            openNotification.dataset.notificationCount = String(count);

            if (count === 0) {
                badge?.remove();
                return;
            }

            if (!badge) {
                badge = document.createElement("p");
                badge.dataset.notificationBadge = "";
                openNotification.appendChild(badge);
            }

            badge.dataset.count = String(count);
            badge.textContent = formatNotificationCount(count);
        };

        const decrementNotificationBadge = () => {
            const currentCount =
                Number.parseInt(openNotification.dataset.notificationCount, 10) ||
                Number.parseInt(
                    openNotification
                        .querySelector("[data-notification-badge]")
                        ?.dataset.count,
                    10,
                ) ||
                0;

            updateNotificationBadge(currentCount - 1);
        };

        const updateEmptyState = () => {
            const emptyState = notificationPanel.querySelector(
                "[data-notification-empty]",
            );

            if (!emptyState) return;

            emptyState.hidden = Boolean(
                notificationPanel.querySelector("[data-notification-item]"),
            );
        };

        const markNotificationRead = (item) => {
            const notificationKey = item.dataset.notificationKey;

            if (!notificationKey || !readUrl || !csrfToken) {
                return Promise.resolve();
            }

            if (item.dataset.notificationReadPending === "1") {
                return Promise.resolve();
            }

            item.dataset.notificationReadPending = "1";
            decrementNotificationBadge();
            item.remove();
            updateEmptyState();

            return fetch(readUrl, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ key: notificationKey }),
            }).catch(() => undefined);
        };

        const setNotificationState = (isActive) => {
            notificationPanel.classList.toggle("active", isActive);
            openNotification.classList.toggle("active", isActive);
            openNotification.setAttribute("aria-expanded", String(isActive));
            notificationPanel.setAttribute("aria-hidden", String(!isActive));
        };

        openNotification.addEventListener("click", function (event) {
            event.preventDefault();
            setNotificationState(
                !notificationPanel.classList.contains("active"),
            );
        });

        closeNotification?.addEventListener("click", function () {
            setNotificationState(false);
        });

        notificationPanel
            .querySelectorAll("[data-notification-item]")
            .forEach((item) => {
                item.addEventListener("click", function (event) {
                    const destination = item.getAttribute("href") || "#";
                    const shouldStayOnPage =
                        destination === "#" || destination.startsWith("javascript:");
                    const isNormalClick =
                        event.button === 0 &&
                        !event.metaKey &&
                        !event.ctrlKey &&
                        !event.shiftKey &&
                        !event.altKey &&
                        !item.target;

                    if (!isNormalClick) {
                        markNotificationRead(item);
                        return;
                    }

                    event.preventDefault();

                    markNotificationRead(item).finally(() => {
                        if (!shouldStayOnPage) {
                            window.location.href = destination;
                        }
                    });
                });
            });

        document.addEventListener("click", function (event) {
            if (
                !notificationPanel.contains(event.target) &&
                !openNotification.contains(event.target)
            ) {
                setNotificationState(false);
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                setNotificationState(false);
            }
        });

        setNotificationState(notificationPanel.classList.contains("active"));
        updateEmptyState();
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const $ = window.jQuery;

    if (!$) return;
    if (!$.fn.DataTable) return;

    const tableDateFilters = [];

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const activeDateFilter = tableDateFilters.find(
            (filter) => filter.table === settings.nTable,
        );

        if (!activeDateFilter) return true;

        const filterValue = activeDateFilter.$filter.val();

        if (!filterValue || filterValue === "all") return true;

        const row = settings.aoData[dataIndex].nTr;
        const dateValue = row
            ?.querySelector(`[data-date-column="${activeDateFilter.column}"]`)
            ?.getAttribute("data-date");

        if (!dateValue) return true;

        const rowDate = new Date(`${dateValue}T00:00:00`);
        const today = new Date();
        const startOfToday = new Date(
            today.getFullYear(),
            today.getMonth(),
            today.getDate(),
        );
        const startOfWeek = new Date(startOfToday);

        startOfWeek.setDate(startOfToday.getDate() - startOfToday.getDay());

        if (filterValue === "today") {
            return rowDate.toDateString() === startOfToday.toDateString();
        }

        if (filterValue === "week") {
            return rowDate >= startOfWeek && rowDate <= startOfToday;
        }

        if (filterValue === "month") {
            return (
                rowDate.getFullYear() === today.getFullYear() &&
                rowDate.getMonth() === today.getMonth()
            );
        }

        return true;
    });

    $(".js-admin-datatable").each(function () {
        const $table = $(this);
        const tableId = $table.attr("id");

        if (!tableId || $.fn.DataTable.isDataTable(this)) return;

        const unsortableColumns = ($table.data("unsortable") || "")
            .toString()
            .split(",")
            .map((index) => Number.parseInt(index.trim(), 10))
            .filter(Number.isInteger);

        const dataTableOptions = {
            pageLength: Number.parseInt($table.data("pageLength"), 10) || 5,
            lengthChange: false,
            autoWidth: false,
            order: [],
            dom: "t<'admin-datatable__footer'ip>",
            language: {
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "No entries found",
                zeroRecords: "No matching records found",
                paginate: {
                    previous: "Previous",
                    next: "Next",
                },
            },
        };

        if (unsortableColumns.length) {
            dataTableOptions.columnDefs = [
                {
                    orderable: false,
                    targets: unsortableColumns,
                },
            ];
        }

        const dataTable = $table.DataTable(dataTableOptions);

        const searchSelector = $table.data("search");

        if (searchSelector) {
            $(searchSelector).on("input", function () {
                dataTable.search(this.value).draw();
            });
        }

        const applyColumnFilter = (filter) => {
            const columnIndex = Number.parseInt($(filter).data("column"), 10);
            const value = filter.value;
            const shouldShowAll =
                !value || value.toString().toLowerCase() === "all";

            if (!Number.isInteger(columnIndex)) return false;

            dataTable.column(columnIndex).search(
                shouldShowAll
                    ? ""
                    : `^${$.fn.dataTable.util.escapeRegex(value)}$`,
                true,
                false,
            );

            return true;
        };

        const $columnFilters = $(
            `[data-datatable-filter][data-table="#${tableId}"]`,
        );

        $columnFilters.on("change", function () {
            if (applyColumnFilter(this)) {
                dataTable.draw();
            }
        });

        const $dateFilters = $(
            `[data-datatable-date-filter][data-table="#${tableId}"]`,
        );

        $dateFilters.each(
            function () {
                const columnIndex = Number.parseInt($(this).data("column"), 10);

                if (!Number.isInteger(columnIndex)) return;

                tableDateFilters.push({
                    table: $table[0],
                    column: columnIndex,
                    $filter: $(this),
                });

                $(this).on("change", function () {
                    dataTable.draw();
                });
            },
        );

        const hasInitialColumnFilter = $columnFilters
            .toArray()
            .some((filter) => applyColumnFilter(filter));

        if (hasInitialColumnFilter || $dateFilters.length) {
            dataTable.draw();
        }
    });

});

//Icon Dropdown
document.addEventListener("DOMContentLoaded", () => {
    // Menjaga modal di luar wrapper halaman agar blur hanya mengenai latar.
    document.querySelectorAll(".modal").forEach((modal) => {
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });

    const blurLayer = document.createElement("div");
    blurLayer.className = "modal-blur-layer";
    blurLayer.setAttribute("aria-hidden", "true");
    document.body.appendChild(blurLayer);

    document.addEventListener("show.bs.modal", () => {
        blurLayer.classList.add("is-active");
    });

    document.addEventListener("hidden.bs.modal", () => {
        if (!document.querySelector(".modal.show")) {
            blurLayer.classList.remove("is-active");
        }
    });

    const initFilterSelect = (selector) => {
        const elements = document.querySelectorAll(selector);

        if (!elements.length) return;

        elements.forEach((element) => {
            if (element.tomselect) return;

            new TomSelect(element, {
                create: false,
                allowEmptyOption: true,
                onChange() {
                    element.dispatchEvent(new Event("change", { bubbles: true }));
                },
            });
        });
    };

    if (document.querySelector("#demoGenreFilter")) {
        initFilterSelect("#demoGenreFilter");
    }

    if (document.querySelector("#styleSamplingGenreFilter")) {
        initFilterSelect("#styleSamplingGenreFilter");
    }
    if (document.querySelector("#styleSamplingPackFilter")) {
        initFilterSelect("#styleSamplingPackFilter");
    }
    if (document.querySelector("#styleSamplingStatusFilter")) {
        initFilterSelect("#styleSamplingStatusFilter");
    }
    if (document.querySelector("#samplingRequestStatusFilter")) {
        initFilterSelect("#samplingRequestStatusFilter");
    }
    if (document.querySelector("#subscriptionStatusFilter")) {
        initFilterSelect("#subscriptionStatusFilter");
    }
    if (document.querySelector("#downloadSalesAccess")) {
        initFilterSelect("#downloadSalesAccess");
    }
    if (document.querySelector("#downloadmonth")) {
        initFilterSelect("#downloadmonth");
    }
    if (document.querySelector("#userManagement")) {
        initFilterSelect("#userManagement");
    }
    if (document.querySelector("#userPlanFilter")) {
        initFilterSelect("#userPlanFilter");
    }

    initFilterSelect("[data-style-filter]");
    initFilterSelect("[data-unified-style-filter]");
    initFilterSelect('.sampling-request-form select[name="pack_name"]');
});
