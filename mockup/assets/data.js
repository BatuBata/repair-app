// Data contoh (sintetis) untuk mockup. Hari ini: Rabu, 11 Nov 2026 (WITA).
// Semua total dihitung dari baris; pengecekan ada di bawah (lihat console).
window.DATA = (() => {
  const TODAY = 11;

  // Nota servis: lines = [deskripsi, qty, harga jual, modal satuan] ; jasa modal 0
  const tts = [
    { no: "TTS-2611-014", nama: "Bu Ratna Sari", hp: "0812-5531-2097", kat: "Mesin cuci", barang: "Sharp 2 tabung, 8 kg", keluhan: "Pengering tidak berputar", status: "diterima", masuk: [11, "10.42"], teknisi: null },
    { no: "TTS-2611-013", nama: "Pak Hendra Wijaya", hp: "0813-4720-1186", kat: "Laptop", barang: "Asus X441, hitam", keluhan: "LCD bergaris, kadang mati sendiri", status: "dikerjakan", masuk: [10, "09.15"], teknisi: "Rizky", sejak: 10 },
    { no: "TTS-2611-012", nama: "Ibu Maya Lestari", hp: "0852-1904-7731", kat: "TV", barang: "Polytron 32 inci", keluhan: "Ada suara, gambar tidak muncul", status: "dikerjakan", masuk: [9, "14.30"], teknisi: "Dimas", sejak: 9 },
    { no: "TTS-2611-011", nama: "Andi Pratama", hp: "0821-6650-3318", kat: "Printer", barang: "Epson L3110", keluhan: "Hasil cetak bergaris", status: "selesai", masuk: [8, "11.05"], teknisi: "Rizky", nota: 10,
      lines: [["Tinta 664 hitam", 1, 100000, 60000], ["Jasa pembersihan head", 1, 85000, 0]] },
    { no: "TTS-2611-010", nama: "Siti Aminah", hp: "0811-5402-8876", kat: "Catokan", barang: "Philips", keluhan: "Tidak panas", status: "selesai", masuk: [7, "16.20"], teknisi: "Agus", nota: 11,
      lines: [["Elemen pemanas", 1, 50000, 25000], ["Jasa ganti elemen", 1, 25000, 0]] },
    { no: "TTS-2611-009", nama: "Yusuf Maulana", hp: "0857-3311-0924", kat: "HP", barang: "Samsung A12", keluhan: "Layar retak, sentuhan tidak berfungsi", status: "diambil", masuk: [6, "10.00"], teknisi: "Dimas", nota: 8,
      lines: [["LCD Samsung A12", 1, 550000, 420000], ["Jasa ganti LCD", 1, 100000, 0]] },
    { no: "TTS-2611-008", nama: "Fajar Nugroho", hp: "0813-2287-6650", kat: "Monitor", barang: "LG 22 inci", keluhan: "Mati total", status: "batal", masuk: [5, "13.40"], teknisi: "Agus" },
    { no: "TTS-2611-007", nama: "Rahmawati", hp: "0812-9044-5170", kat: "Mesin cuci", barang: "Polytron 1 tabung", keluhan: "Bocor di bagian bawah", status: "diambil", masuk: [4, "09.30"], teknisi: "Rizky", nota: 5,
      lines: [["Seal tabung", 1, 100000, 60000], ["Jasa bongkar pasang", 1, 150000, 0]] },
    { no: "TTS-2611-006", nama: "Pak Gunawan", hp: "0822-4501-7789", kat: "Laptop", barang: "Lenovo Ideapad", keluhan: "Lambat, tidak bisa update", status: "diambil", masuk: [3, "15.10"], teknisi: "Rizky", nota: 5,
      lines: [["SSD 120 GB", 1, 350000, 270000], ["Jasa pasang & instal ulang", 1, 100000, 0]] },
    { no: "TTS-2611-005", nama: "Bu Sri Wahyuni", hp: "0813-7760-2241", kat: "TV", barang: "Sharp 40 inci", keluhan: "Garis vertikal di layar", status: "diambil", masuk: [2, "11.45"], teknisi: "Dimas", nota: 4,
      lines: [["T-con board", 1, 650000, 480000], ["Jasa ganti board", 1, 150000, 0]] },
    { no: "TTS-2611-004", nama: "Dedi Kurniawan", hp: "0852-6630-1192", kat: "Komputer", barang: "Rakitan", keluhan: "Mati total", status: "diambil", masuk: [2, "09.20"], teknisi: "Agus", nota: 3,
      lines: [["Power supply 450 W", 1, 450000, 320000], ["Jasa ganti PSU", 1, 75000, 0]] },
    { no: "TTS-2611-003", nama: "Lina Marlina", hp: "0812-3378-4405", kat: "Printer", barang: "Canon iP2770", keluhan: "Kertas macet", status: "diambil", masuk: [1, "13.00"], teknisi: "Agus", nota: 2,
      lines: [["Jasa servis penarik kertas", 1, 75000, 0]] },
    { no: "TTS-2611-002", nama: "Pak Budi Santoso", hp: "0811-2093-5567", kat: "Mesin cuci", barang: "Sanken 2 tabung", keluhan: "Dinamo pengering mati", status: "diambil", masuk: [1, "10.10"], teknisi: "Rizky", nota: 2,
      lines: [["Dinamo pengering", 1, 250000, 200000], ["Jasa ganti dinamo", 1, 100000, 0]] },
    { no: "TTS-2611-001", nama: "Rina Puspita", hp: "0857-9921-3046", kat: "HP", barang: "Oppo A5s", keluhan: "Tidak bisa dicas", status: "diambil", masuk: [1, "08.40"], teknisi: "Dimas", nota: 1,
      lines: [["Konektor cas", 1, 100000, 40000], ["Jasa ganti konektor", 1, 75000, 0]] },
  ];

  const jual = [
    { no: "JL-2611-001", tgl: 2, ket: "Flashdisk 32 GB", qty: 2, harga: 75000, modal: 45000 },
    { no: "JL-2611-002", tgl: 3, ket: "Printer bekas Canon iP2770", qty: 1, harga: 650000, modal: 450000 },
    { no: "JL-2611-003", tgl: 5, ket: "Kabel HDMI 3 m", qty: 1, harga: 60000, modal: 30000 },
    { no: "JL-2611-004", tgl: 7, ket: "Mouse wireless", qty: 2, harga: 85000, modal: 50000 },
    { no: "JL-2611-005", tgl: 9, ket: "Keyboard USB", qty: 1, harga: 95000, modal: 55000 },
    { no: "JL-2611-006", tgl: 11, ket: "Printer bekas Epson L120", qty: 1, harga: 1500000, modal: 1100000 },
  ];

  const weekdays = [2, 3, 4, 5, 6, 9, 10, 11]; // 1 Nov 2026 hari Minggu
  const keluar = [
    ...weekdays.map((d) => ({ tgl: d, kat: "Bensin", ket: "2 motor teknisi", nominal: 15000 })),
    { tgl: 2, kat: "Konsumsi", ket: "Makan siang", nominal: 50000 },
    { tgl: 4, kat: "Lainnya", ket: "Kertas thermal 58 mm, 1 pak", nominal: 45000 },
    { tgl: 5, kat: "Konsumsi", ket: "Makan siang", nominal: 50000 },
    { tgl: 9, kat: "Konsumsi", ket: "Makan siang", nominal: 50000 },
  ];

  const garansi = [
    { no: "JL-2610-014", nama: "Bu Dewi Kartika", barang: "Printer bekas Canon", akhir: 12, sisa: "besok" },
    { no: "TTS-2610-041", nama: "Pak Joko Susilo", barang: "TV Sharp 32 inci", akhir: 13, sisa: "2 hari lagi" },
  ];

  const sum = (a, f) => a.reduce((s, x) => s + f(x), 0);
  const jualOf = (t) => sum(t.lines || [], (l) => l[1] * l[2]);
  const modalOf = (t) => sum(t.lines || [], (l) => l[1] * l[3]);
  const nota = tts.filter((t) => t.nota);
  const days = Array.from({ length: TODAY }, (_, i) => i + 1);
  const daily = days.map((d) => {
    const n = nota.filter((t) => t.nota === d), j = jual.filter((x) => x.tgl === d);
    const omzet = sum(n, jualOf) + sum(j, (x) => x.qty * x.harga);
    const modal = sum(n, modalOf) + sum(j, (x) => x.qty * x.modal);
    return { d, omzet, modal, laba: omzet - modal, nota: n.length };
  });

  const servis = { omzet: sum(nota, jualOf), modal: sum(nota, modalOf), count: nota.length };
  const penjualan = { omzet: sum(jual, (x) => x.qty * x.harga), modal: sum(jual, (x) => x.qty * x.modal), count: jual.length };
  const omzet = servis.omzet + penjualan.omzet, modal = servis.modal + penjualan.modal;
  const pengeluaran = sum(keluar, (x) => x.nominal);
  const report = { servis, penjualan, omzet, modal, labaKotor: omzet - modal, pengeluaran, labaBersih: omzet - modal - pengeluaran };

  console.assert(sum(daily, (x) => x.omzet) === omzet, "omzet harian ≠ total");
  console.assert(sum(daily, (x) => x.laba) === report.labaKotor, "laba harian ≠ laba kotor");
  console.assert(omzet === 6160000 && report.labaBersih === 2145000, "angka laporan berubah", report);
  const counts = tts.reduce((c, t) => ((c[t.status] = (c[t.status] || 0) + 1), c), {});
  console.assert(counts.diterima === 1 && counts.dikerjakan === 2 && counts.selesai === 2 && counts.diambil === 8 && counts.batal === 1, "jumlah status", counts);

  return { TODAY, tts, jual, keluar, garansi, daily, report, counts, jualOf, modalOf };
})();
