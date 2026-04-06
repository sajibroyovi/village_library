/**
 * Dashboard Features Logic - Shidhlajury Refactoring
 * Handles Analytics, Map, Newsfeed, Notifications, and PDF Exports
 */

// --- PDF Export Logic ---
async function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const family = globalFamiliesData.find(f => f.id == currentViewFamilyId);
    const fileName = `${family.house_owner_name.replace(/\s+/g, '_')}_Family_${currentMemberView}.pdf`;
    showToast("Generating PDF... please wait.");

    if (currentMemberView === 'list') {
        const doc = new jsPDF();
        doc.setFontSize(18);
        doc.text(`${family.house_owner_name}'s Family Directory`, 14, 20);
        doc.setFontSize(11);
        doc.setTextColor(100);
        doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 14, 28);
        
        let yPos = 40;
        family.members.forEach((m, index) => {
            if (yPos > 270) { doc.addPage(); yPos = 20; }
            doc.setFontSize(12); doc.setTextColor(0);
            doc.text(`${index + 1}. ${m.name}`, 14, yPos);
            doc.setFontSize(10); doc.setTextColor(80);
            doc.text(`Relation: ${m.relation_to_owner} | Mobile: ${m.mobile_number || 'N/A'}`, 20, yPos + 6);
            if (m.parent_member_id) {
                const parent = family.members.find(p => p.id == m.parent_member_id);
                if (parent) { doc.setTextColor(120); doc.text(`Father/Parent: ${parent.name}`, 20, yPos + 11); yPos += 5; }
            }
            yPos += 15;
        });
        doc.save(fileName);
        showToast("PDF Downloaded successfully!");
    } else {
        const treeElement = document.getElementById('premium-tree-mount');
        const originalPadding = treeElement.style.padding;
        const originalOverflow = treeElement.style.overflow;
        treeElement.style.padding = '40px'; treeElement.style.overflow = 'visible'; treeElement.style.width = 'fit-content';

        try {
            const canvas = await html2canvas(treeElement, { backgroundColor: '#0f172a', scale: 1.5, useCORS: true, logging: false });
            const pdf = new jsPDF({ orientation: canvas.width > canvas.height ? 'l' : 'p', unit: 'px', format: [canvas.width, canvas.height] });
            pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, canvas.width, canvas.height);
            pdf.save(fileName);
            showToast("Family Tree PDF Downloaded!");
        } catch (err) {
            console.error("PDF Export Error:", err);
            showToast("Error generating tree PDF.", "error");
        } finally {
            treeElement.style.padding = originalPadding; treeElement.style.overflow = originalOverflow; treeElement.style.width = '';
        }
    }
}

// --- Analytics Module ---
let analyticsCharts = {};
function renderAnalyticsCharts() {
    if (globalFamiliesData.length === 0) return;
    let totalHouseholds = globalFamiliesData.length, totalPopulation = 0, employedCount = 0;
    let areaCount = {}, financeCount = {}, bloodCount = {}, eduCount = {};

    globalFamiliesData.forEach(f => {
        totalPopulation += (f.members?.length || 0);
        areaCount[f.area || 'Unknown'] = (areaCount[f.area || 'Unknown'] || 0) + 1;
        financeCount[f.financial_condition || 'Unknown'] = (financeCount[f.financial_condition || 'Unknown'] || 0) + 1;
        if (f.members) {
            f.members.forEach(m => {
                const job = m.job_status ? m.job_status.toLowerCase() : '';
                if (job && job !== 'student' && job !== 'unemployed' && job !== 'housewife') employedCount++;
                if (m.blood_group) bloodCount[m.blood_group] = (bloodCount[m.blood_group] || 0) + 1;
                if (m.education) eduCount[m.education] = (eduCount[m.education] || 0) + 1;
            });
        }
    });

    document.getElementById('kpiHouseholds').innerText = totalHouseholds;
    document.getElementById('kpiPopulation').innerText = totalPopulation;
    document.getElementById('kpiAvgSize').innerText = totalHouseholds ? (totalPopulation / totalHouseholds).toFixed(1) : 0;
    document.getElementById('kpiEmployed').innerText = employedCount;

    Chart.defaults.color = '#818cf8'; 
    const createChart = (id, type, dataObj, bgColor, label) => {
        if (!document.getElementById(id)) return;
        if (analyticsCharts[id]) analyticsCharts[id].destroy();
        analyticsCharts[id] = new Chart(document.getElementById(id), {
            type: type,
            data: { labels: Object.keys(dataObj).map(l => l.charAt(0).toUpperCase() + l.slice(1)), datasets: [{ label: label, data: Object.values(dataObj), backgroundColor: bgColor, borderWidth: 1, borderColor: '#ffffff' }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: type === 'pie' || type === 'doughnut', position: 'right' } } }
        });
    };
    const colors = ['#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#f59e0b', '#10b981', '#3b82f6', '#14b8a6'];
    createChart('chartArea', 'bar', areaCount, colors[0], 'Households');
    createChart('chartFinance', 'doughnut', financeCount, colors, 'Households');
    createChart('chartBlood', 'pie', bloodCount, colors, 'Members');
}

// --- Village Map Module ---
let leafletMap = null;
const VILLAGE_CENTER = [23.4825, 90.3872];
const AREA_COORDS = { 'purbo para': [23.4820, 90.3870], 'uttor para': [23.4845, 90.3880], 'dokhin para': [23.4795, 90.3875], 'roy bari': [23.4830, 90.3855], 'boshu para': [23.4810, 90.3900], 'babu para': [23.4855, 90.3860], 'porchim para': [23.4800, 90.3840] };

function renderVillageMap() {
    if (leafletMap) { leafletMap.remove(); leafletMap = null; }
    leafletMap = L.map('villageMap').setView(VILLAGE_CENTER, 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(leafletMap);
    const houseIcon = L.divIcon({ className: '', html: `<div style="background:#4f46e5;width:34px;height:34px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;">🏠</div>`, iconSize: [34, 34], iconAnchor: [17, 34], popupAnchor: [0, -36] });
    
    globalFamiliesData.forEach(f => {
        const coords = parseLatLng(f.google_map_location);
        if (coords) L.marker(coords, { icon: houseIcon }).addTo(leafletMap).bindPopup(`<b>${f.house_owner_name}</b><br>📍 ${f.area}`);
        else {
            const base = AREA_COORDS[f.area?.toLowerCase()] || VILLAGE_CENTER;
            const jitter = [base[0] + (Math.random() - 0.5) * 0.002, base[1] + (Math.random() - 0.5) * 0.002];
            L.circleMarker(jitter, { radius: 9, fillColor: '#a5b4fc', color: '#4f46e5', weight: 2, fillOpacity: 0.7 }).addTo(leafletMap).bindPopup(`<b>${f.house_owner_name}</b>`);
        }
    });
}
function parseLatLng(url) { if (!url) return null; const reg = /@([-\d.]+),([-\d.]+)/; const m = url.match(reg); return m ? [parseFloat(m[1]), parseFloat(m[2])] : null; }

// --- Newsfeed Module ---
function renderNewsfeed() {
    const timeline = document.getElementById('newsfeedTimeline');
    if (!timeline) return;
    const events = globalFamiliesData.map(f => ({ icon: '🏠', color: '#4f46e5', title: `Household Added`, desc: `<b>${f.house_owner_name}</b> was added to ${f.area}` }));
    timeline.innerHTML = events.slice(0, 20).map(ev => `
        <div style="display:flex;gap:16px;padding-bottom:24px;">
            <div style="width:40px;height:40px;background:${ev.color}18;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;border:2px solid ${ev.color}30;">${ev.icon}</div>
            <div style="background:white;border:1px solid #e0e7ff;border-radius:12px;padding:14px;flex:1;">
                <div style="font-size:0.7rem;font-weight:800;text-transform:uppercase;color:${ev.color};">${ev.title}</div>
                <div style="font-size:0.88rem;color:#1e1b4b;line-height:1.5;">${ev.desc}</div>
            </div>
        </div>`).join('');
}
