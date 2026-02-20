const STORAGE_KEY = "salesReports";

const form = document.getElementById("sales-form");
const rows = document.getElementById("report-rows");
const summary = document.getElementById("summary");

const filterBranch = document.getElementById("filter-branch");
const filterSeller = document.getElementById("filter-seller");
const filterFrom = document.getElementById("filter-from");
const filterTo = document.getElementById("filter-to");

const exportCsvButton = document.getElementById("export-csv");
const clearDataButton = document.getElementById("clear-data");

function loadReports() {
  return JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
}

function saveReports(data) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

function formatMoney(amount) {
  return new Intl.NumberFormat("es-MX", {
    style: "currency",
    currency: "MXN",
  }).format(amount);
}

function getFilteredReports() {
  const branchText = filterBranch.value.trim().toLowerCase();
  const sellerText = filterSeller.value.trim().toLowerCase();
  const fromDate = filterFrom.value;
  const toDate = filterTo.value;

  return loadReports().filter((report) => {
    const matchesBranch = !branchText || report.branch.toLowerCase().includes(branchText);
    const matchesSeller = !sellerText || report.seller.toLowerCase().includes(sellerText);
    const matchesFrom = !fromDate || report.date >= fromDate;
    const matchesTo = !toDate || report.date <= toDate;

    return matchesBranch && matchesSeller && matchesFrom && matchesTo;
  });
}

function renderSummary(reports) {
  const totalSales = reports.reduce((sum, r) => sum + r.amount, 0);
  const vendors = new Set(reports.map((r) => r.seller)).size;
  const branches = new Set(reports.map((r) => r.branch)).size;

  summary.innerHTML = `
    <strong>Total de registros:</strong> ${reports.length}<br>
    <strong>Total de ventas:</strong> ${formatMoney(totalSales)}<br>
    <strong>Vendedores únicos:</strong> ${vendors}<br>
    <strong>Sucursales únicas:</strong> ${branches}
  `;
}

function renderRows() {
  const reports = getFilteredReports().sort((a, b) => b.date.localeCompare(a.date));

  rows.innerHTML = reports
    .map(
      (r) => `
      <tr>
        <td>${r.date}</td>
        <td>${r.branch}</td>
        <td>${r.manager}</td>
        <td>${r.seller}</td>
        <td>${formatMoney(r.amount)}</td>
        <td>${r.notes || "-"}</td>
      </tr>
    `,
    )
    .join("");

  if (!reports.length) {
    rows.innerHTML = '<tr><td colspan="6">No hay reportes con los filtros actuales.</td></tr>';
  }

  renderSummary(reports);
}

function toCsv(reports) {
  const headers = ["Fecha", "Sucursal", "Encargado", "Vendedor", "Ventas", "Comentarios"];
  const lines = reports.map((r) =>
    [r.date, r.branch, r.manager, r.seller, r.amount.toFixed(2), r.notes || ""]
      .map((field) => `"${String(field).replaceAll('"', '""')}"`)
      .join(","),
  );
  return [headers.join(","), ...lines].join("\n");
}

function downloadCsv() {
  const reports = getFilteredReports();
  const csvContent = toCsv(reports);
  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");

  link.href = url;
  link.download = "reporte-ventas.csv";
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

form.addEventListener("submit", (event) => {
  event.preventDefault();
  const formData = new FormData(form);

  const report = {
    branch: formData.get("branch").trim(),
    manager: formData.get("manager").trim(),
    seller: formData.get("seller").trim(),
    date: formData.get("date"),
    amount: Number(formData.get("amount")),
    notes: formData.get("notes").trim(),
  };

  if (!report.branch || !report.manager || !report.seller || !report.date || Number.isNaN(report.amount)) {
    return;
  }

  const reports = loadReports();
  reports.push(report);
  saveReports(reports);

  form.reset();
  renderRows();
});

[filterBranch, filterSeller, filterFrom, filterTo].forEach((input) => {
  input.addEventListener("input", renderRows);
});

exportCsvButton.addEventListener("click", downloadCsv);

clearDataButton.addEventListener("click", () => {
  const confirmed = window.confirm("¿Seguro que deseas borrar todos los reportes?");
  if (!confirmed) {
    return;
  }

  localStorage.removeItem(STORAGE_KEY);
  renderRows();
});

renderRows();
