class DataTableHandler {
    constructor(id) {
        this.table = document.getElementById(id);
        if (this.table) this.init();
    }

    init() {
        this.table.querySelectorAll("th[data-sort]").forEach(th => {
            th.style.cursor = "pointer";
            th.addEventListener("click", () => {
                console.log("Sorting:", th.dataset.sort);
            });
        });
    }
}

window.DataTableHandler = DataTableHandler;
