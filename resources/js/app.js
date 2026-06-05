import "./bootstrap";
import TomSelect from "tom-select";
import "tom-select/dist/css/tom-select.css";

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

        if (!notificationPanel) return;

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

        $(`[data-datatable-filter][data-table="#${tableId}"]`).on(
            "change",
            function () {
                const columnIndex = Number.parseInt($(this).data("column"), 10);
                const value = this.value;
                const shouldShowAll =
                    !value || value.toString().toLowerCase() === "all";

                if (!Number.isInteger(columnIndex)) return;

                dataTable
                    .column(columnIndex)
                    .search(
                        shouldShowAll
                            ? ""
                            : `^${$.fn.dataTable.util.escapeRegex(value)}$`,
                        true,
                        false,
                    )
                    .draw();
            },
        );

        $(`[data-datatable-date-filter][data-table="#${tableId}"]`).each(
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
    });

});

//Icon Dropdown
document.addEventListener("DOMContentLoaded", () => {
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
    if (document.querySelector("#downloadSalesType")) {
        initFilterSelect("#downloadSalesType");
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
    initFilterSelect('.sampling-request-form select[name="pack_name"]');
});
