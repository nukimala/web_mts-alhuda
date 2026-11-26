<?php
/**
 * View: admin/kontak/index.php
 * Manajemen Kotak Masuk & Pesan Kontak Pengunjung — Panel Admin MTs Al-Huda
 */

$counts = $data['counts'] ?? ['semua' => 0, 'baru' => 0, 'dibaca' => 0, 'dibalas' => 0];
$messages = $data['messages'] ?? [];
$currentStatus = (string) ($data['status'] ?? 'semua');
$currentQ = (string) ($data['q'] ?? '');
$currentPage = (int) ($data['page'] ?? 1);
$totalPages = (int) ($data['total_pages'] ?? 1);
$total = (int) ($data['total'] ?? 0);

ob_start();
?>

<style>
.kontak-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}
.kontak-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: var(--radius-full);
    border: 1px solid var(--border);
    background: #fff;
    color: var(--gray);
    font-size: 12.5px;
    font-weight: 600;
    transition: all .15s ease;
}
.kontak-tab:hover {
    border-color: var(--green);
    color: var(--green);
}
.kontak-tab.on {
    background: var(--green);
    border-color: var(--green);
    color: #fff;
}
.kontak-tab .cnt {
    padding: 1px 7px;
    border-radius: var(--radius-full);
    background: rgba(15, 23, 42, 0.08);
    font-size: 11px;
    font-weight: 700;
}
.kontak-tab.on .cnt {
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
}
.msg-unread td {
    background: #F0FDF4;
}
.msg-snippet {
    color: var(--gray);
    font-size: 12.5px;
    margin-top: 3px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.kontak-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 100;
}
.kontak-modal-backdrop.open {
    display: flex;
}
.kontak-modal {
    background: #fff;
    border-radius: var(--radius-lg);
    width: 100%;
    max-width: 640px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}
.kontak-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid var(--border);
}
.kontak-modal-body {
    padding: 20px 22px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.kontak-meta-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px 16px;
    background: var(--soft);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 12px 14px;
    font-size: 12.5px;
}
.kontak-meta-grid small {
    display: block;
    color: var(--gray);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.kontak-message-box {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 14px;
    white-space: pre-wrap;
    line-height: 1.6;
    font-size: 13.5px;
}
@media (max-width: 640px) {
    .kontak-meta-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Kotak Masuk Pesan</b>
</div>

<div class="page-head">
    <div>
        <h1>Kotak Masuk Pesan</h1>
        <p><?= (int) $counts['baru'] ?> pesan baru menunggu dibaca &middot; Total <?= (int) $counts['semua'] ?> pesan masuk</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline btn-sm" href="<?= base_url('kontak') ?>" target="_blank">
            Lihat Halaman Kontak Publik ↗
        </a>
    </div>
</div>

<?php if (!empty($data['flash'])): ?>
    <div class="notice ok" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></svg>
        <div><?= e($data['flash']) ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($data['error'])): ?>
    <div class="notice err" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
        <div><?= e($data['error']) ?></div>
    </div>
<?php endif; ?>

<div class="grid grid-4" style="margin-bottom:20px">
    <div class="card stat">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div>
            <small>Total Pesan</small>
            <strong><?= (int) $counts['semua'] ?></strong>
            <div class="hint">Semua pesan aktif</div>
        </div>
    </div>

    <div class="card stat">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
        </div>
        <div>
            <small>Pesan Baru</small>
            <strong><?= (int) $counts['baru'] ?></strong>
            <div class="hint">Belum dibaca admin</div>
        </div>
    </div>

    <div class="card stat">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6"/><circle cx="12" cy="12" r="2.6"/></svg>
        </div>
        <div>
            <small>Sudah Dibaca</small>
            <strong><?= (int) $counts['dibaca'] ?></strong>
            <div class="hint">Menunggu tindak lanjut</div>
        </div>
    </div>

    <div class="card stat">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></svg>
        </div>
        <div>
            <small>Sudah Dibalas</small>
            <strong><?= (int) $counts['dibalas'] ?></strong>
            <div class="hint">Selesai ditindaklanjuti</div>
        </div>
    </div>
</div>

<div class="kontak-tabs">
    <?php
    $tabs = [
        'semua' => 'Semua Pesan',
        'baru' => 'Baru',
        'dibaca' => 'Sudah Dibaca',
        'dibalas' => 'Sudah Dibalas',
    ];
    foreach ($tabs as $key => $label):
        $tabUrl = base_url('admin/kontak') . '?' . http_build_query(array_filter([
            'status' => $key,
            'q' => $currentQ !== '' ? $currentQ : null,
        ]));
    ?>
        <a href="<?= $tabUrl ?>" class="kontak-tab <?= $currentStatus === $key ? 'on' : '' ?>">
            <span><?= $label ?></span>
            <span class="cnt"><?= (int) ($counts[$key] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <form method="get" action="<?= base_url('admin/kontak') ?>" class="toolbar">
        <input type="hidden" name="status" value="<?= e($currentStatus) ?>">
        <div class="field-inline" style="min-width:260px">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="text" name="q" value="<?= e($currentQ) ?>" placeholder="Cari nama, email, telepon, subjek, isi...">
        </div>

        <button type="submit" class="btn btn-outline btn-sm">Cari</button>
        <?php if ($currentQ !== '' || $currentStatus !== 'semua'): ?>
            <a href="<?= base_url('admin/kontak') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>

        <div class="right">
            <span class="pill plain gray"><?= $total ?> pesan</span>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:44px">No</th>
                    <th>Pengirim</th>
                    <th>Subjek &amp; Isi Pesan</th>
                    <th>Status</th>
                    <th>Waktu Masuk</th>
                    <th style="width:140px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($messages)): ?>
                <tr>
                    <td colspan="6" style="padding:36px 16px;text-align:center;color:#6B7280">
                        <svg viewBox="0 0 24 24" style="width:32px;height:32px;stroke:#94A3B8;fill:none;stroke-width:1.8;margin:0 auto 8px;display:block"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <b>Belum ada pesan kontak yang sesuai filter.</b>
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1 + (($currentPage - 1) * 15); foreach ($messages as $m): ?>
                    <?php
                    $isNew = ($m['status'] ?? '') === 'baru';
                    $payloadJson = htmlspecialchars(json_encode([
                        'id' => (int) $m['id'],
                        'nama' => (string) ($m['nama'] ?? ''),
                        'email' => (string) ($m['email'] ?? ''),
                        'telepon' => (string) ($m['telepon'] ?? ''),
                        'subjek' => (string) ($m['subjek'] ?? ''),
                        'pesan' => (string) ($m['pesan'] ?? ''),
                        'status' => (string) ($m['status'] ?? 'baru'),
                        'balasan' => (string) ($m['balasan'] ?? ''),
                        'dibalas_oleh_nama' => (string) ($m['dibalas_oleh_nama'] ?? ''),
                        'dibalas_pada' => !empty($m['dibalas_pada']) ? date('d M Y H:i', strtotime((string) $m['dibalas_pada'])) . ' WIB' : '',
                        'created_at' => !empty($m['created_at']) ? date('d M Y H:i', strtotime((string) $m['created_at'])) . ' WIB' : '',
                    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="<?= $isNew ? 'msg-unread' : '' ?>">
                        <td class="muted"><?= $no++ ?></td>
                        <td>
                            <div class="t-title"><?= e($m['nama'] ?? '-') ?></div>
                            <div class="t-sub"><?= e($m['email'] ?? '-') ?></div>
                            <?php if (!empty($m['telepon'])): ?>
                                <div class="t-sub" style="color:#15803D"><?= e($m['telepon']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="t-title"><?= e($m['subjek'] ?? 'Pertanyaan Umum') ?></div>
                            <div class="msg-snippet"><?= e($m['pesan'] ?? '') ?></div>
                            <?php if (!empty($m['balasan'])): ?>
                                <div class="t-sub" style="color:#15803D;margin-top:4px">
                                    Catatan balasan: <?= e(mb_strimwidth((string) $m['balasan'], 0, 70, '...')) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($m['status'] === 'baru'): ?>
                                <span class="pill amber">Baru</span>
                            <?php elseif ($m['status'] === 'dibaca'): ?>
                                <span class="pill blue">Dibaca</span>
                            <?php else: ?>
                                <span class="pill green">Dibalas</span>
                            <?php endif; ?>
                            <?php if (!empty($m['dibalas_oleh_nama'])): ?>
                                <div class="t-sub" style="margin-top:4px">Oleh: <?= e($m['dibalas_oleh_nama']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="muted">
                            <div><?= !empty($m['created_at']) ? date('d M Y', strtotime((string) $m['created_at'])) : '-' ?></div>
                            <div class="t-sub"><?= !empty($m['created_at']) ? date('H:i', strtotime((string) $m['created_at'])) . ' WIB' : '' ?></div>
                        </td>
                        <td>
                            <div class="row-actions">
                                <button type="button"
                                        class="icon-btn"
                                        title="Baca &amp; Tindak Lanjuti"
                                        onclick="openKontakModal(<?= $payloadJson ?>)">
                                    <svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6"/><circle cx="12" cy="12" r="2.6"/></svg>
                                </button>

                                <?php if ($isNew): ?>
                                    <form method="post" action="<?= base_url('admin/kontak/mark-read/' . (int) $m['id']) ?>" style="display:inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="redirect_status" value="<?= e($currentStatus) ?>">
                                        <input type="hidden" name="redirect_q" value="<?= e($currentQ) ?>">
                                        <input type="hidden" name="redirect_page" value="<?= $currentPage ?>">
                                        <button type="submit" class="icon-btn" title="Tandai Sudah Dibaca" style="color:#2563EB">
                                            <svg viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></svg>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="post"
                                      action="<?= base_url('admin/kontak/delete/' . (int) $m['id']) ?>"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesan ini?');"
                                      style="display:inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="redirect_status" value="<?= e($currentStatus) ?>">
                                    <input type="hidden" name="redirect_q" value="<?= e($currentQ) ?>">
                                    <input type="hidden" name="redirect_page" value="<?= $currentPage ?>">
                                    <button type="submit" class="icon-btn" title="Hapus" style="color:#DC2626">
                                        <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <span class="spacer">Halaman <?= $currentPage ?> dari <?= $totalPages ?> (Total <?= $total ?> pesan)</span>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php
                $query = $_GET;
                $query['page'] = $p;
                $pageUrl = base_url('admin/kontak') . '?' . http_build_query($query);
                ?>
                <a href="<?= $pageUrl ?>" class="<?= $p === $currentPage ? 'on' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Detail & Tindak Lanjut Pesan -->
<div id="kontakModalBackdrop" class="kontak-modal-backdrop" onclick="if (event.target === this) closeKontakModal();">
    <div class="kontak-modal" role="dialog" aria-modal="true" aria-labelledby="kmSubject">
        <div class="kontak-modal-head">
            <div>
                <h3 id="kmSubject" style="font-size:16px">Detail Pesan</h3>
                <div id="kmDate" class="t-sub" style="margin-top:2px"></div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeKontakModal()">Tutup ✕</button>
        </div>
        <div class="kontak-modal-body">
            <div class="kontak-meta-grid">
                <div>
                    <small>Pengirim</small>
                    <strong id="kmNama"></strong>
                </div>
                <div>
                    <small>Email</small>
                    <a id="kmEmailLink" href="#"></a>
                </div>
                <div>
                    <small>Telepon / WhatsApp</small>
                    <span id="kmTelepon">-</span>
                </div>
                <div>
                    <small>Status</small>
                    <span id="kmStatusBadge"></span>
                </div>
            </div>

            <div>
                <small style="display:block;font-weight:700;color:var(--gray);margin-bottom:6px">ISI PESAN</small>
                <div id="kmPesan" class="kontak-message-box"></div>
            </div>

            <div id="kmQuickLinks" style="display:flex;gap:8px;flex-wrap:wrap"></div>

            <form id="kmReplyForm" method="post" action="">
                <?= Csrf::field() ?>
                <input type="hidden" name="redirect_status" value="<?= e($currentStatus) ?>">
                <input type="hidden" name="redirect_q" value="<?= e($currentQ) ?>">
                <input type="hidden" name="redirect_page" value="<?= $currentPage ?>">

                <label for="kmBalasan" style="display:block;font-size:12px;font-weight:700;color:var(--gray);margin-bottom:6px">
                    CATATAN BALASAN / TINDAK LANJUT ADMIN (OPSIONAL)
                </label>
                <textarea id="kmBalasan"
                          name="balasan"
                          rows="3"
                          placeholder="Tulis catatan balasan atau ringkasan tindak lanjut via Email/WhatsApp..."
                          style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-md);resize:vertical"></textarea>
                <div id="kmRepliedMeta" class="t-sub" style="margin-top:6px"></div>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px">
                    <button type="button" class="btn btn-outline btn-sm" onclick="closeKontakModal()">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan &amp; Tandai Sudah Dibalas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openKontakModal(item) {
    document.getElementById('kmSubject').textContent = item.subjek || 'Pertanyaan Umum';
    document.getElementById('kmDate').textContent = 'Diterima: ' + (item.created_at || '-');
    document.getElementById('kmNama').textContent = item.nama || '-';

    var emailLink = document.getElementById('kmEmailLink');
    emailLink.textContent = item.email || '-';
    emailLink.href = item.email ? ('mailto:' + item.email + '?subject=' + encodeURIComponent('Re: ' + (item.subjek || 'Pesan Anda ke MTs Al-Huda'))) : '#';

    document.getElementById('kmTelepon').textContent = item.telepon || '-';
    document.getElementById('kmPesan').textContent = item.pesan || '';
    document.getElementById('kmBalasan').value = item.balasan || '';

    var statusLabels = { baru: 'Baru', dibaca: 'Sudah Dibaca', dibalas: 'Sudah Dibalas' };
    document.getElementById('kmStatusBadge').textContent = statusLabels[item.status] || item.status;

    var repliedMeta = document.getElementById('kmRepliedMeta');
    if (item.dibalas_pada) {
        repliedMeta.textContent = 'Terakhir dibalas ' + item.dibalas_pada + (item.dibalas_oleh_nama ? ' oleh ' + item.dibalas_oleh_nama : '');
    } else {
        repliedMeta.textContent = '';
    }

    var quickLinks = document.getElementById('kmQuickLinks');
    quickLinks.innerHTML = '';
    if (item.email) {
        var mailBtn = document.createElement('a');
        mailBtn.className = 'btn btn-outline btn-sm';
        mailBtn.href = 'mailto:' + item.email + '?subject=' + encodeURIComponent('Re: ' + (item.subjek || 'MTs Al-Huda Gondang'));
        mailBtn.textContent = 'Balas via Email ↗';
        quickLinks.appendChild(mailBtn);
    }
    if (item.telepon) {
        var digits = item.telepon.replace(/[^0-9]/g, '');
        if (digits.startsWith('0')) {
            digits = '62' + digits.slice(1);
        }
        if (digits.length >= 8) {
            var waBtn = document.createElement('a');
            waBtn.className = 'btn btn-outline btn-sm';
            waBtn.target = '_blank';
            waBtn.rel = 'noopener noreferrer';
            waBtn.href = 'https://wa.me/' + digits + '?text=' + encodeURIComponent('Assalamualaikum ' + (item.nama || '') + ', menindaklanjuti pesan Anda di website MTs Al-Huda Gondang...');
            waBtn.textContent = 'Balas via WhatsApp ↗';
            quickLinks.appendChild(waBtn);
        }
    }

    document.getElementById('kmReplyForm').action = '<?= base_url('admin/kontak/mark-replied/') ?>' + item.id;
    document.getElementById('kontakModalBackdrop').classList.add('open');
}

function closeKontakModal() {
    document.getElementById('kontakModalBackdrop').classList.remove('open');
}
</script>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
