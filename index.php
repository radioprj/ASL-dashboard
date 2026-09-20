<?php

$config = parse_ini_file('config.ini', true);
$nodeNumber = isset($config['node']['number']) ? $config['node']['number'] : '123456';
$nodeTitle = isset($config['node']['callsign']) ? $config['node']['callsign'] : 'N0CALL';
$nodeIp = isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '' ? $_SERVER['SERVER_ADDR'] : gethostbyname(gethostname());

$buttons = parse_ini_file('buttons.ini', true);

require __DIR__ . '/lib/ipaccess.php';
$isInternalClient = is_internal_client(load_allowed_networks($config));

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($nodeTitle); ?> | Transceiver Node <?php echo htmlspecialchars($nodeNumber); ?></title>
        <meta name="description" content="<?php echo htmlspecialchars($nodeTitle); ?> - AllStarLink Ham Radio Control Panel">
        <meta name="robots" content="noindex, nofollow">
        <meta name="author" content="M0NFI, G7RPG, G4IYT, SP2ONG">
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
        <link rel="icon" href="favicon.ico" type="image/x-icon">
        <link rel="stylesheet" href="css/dashboard-live.css">
    </head>
    <body>
        <div class="rig-wrapper">
            <!-- Main Radio Transceiver Chassis -->
            <div class="rig-chassis">
                <!-- Offline banner: shown when collector.php stops updating state.json -->
                <div class="rpt-offline-banner" id="rpt-offline-banner">⚠ RPT OFFLINE — COLLECTOR NOT RESPONDING</div>

                <!-- Top Bezel / Rack Screws & Rig Badge -->
                <div class="rig-top-bar">
                    <div class="hex-screw top-left"></div>
                    <div class="rig-brand">
                        <span class="brand-name">ASL Dashboard</span>
                        <span class="model-badge">DSP-<?php echo htmlspecialchars($nodeNumber); ?> PRO</span>
                        <span class="model-desc">ALLSTARLINK DIGITAL TRANSCEIVER</span>
                    </div>
                    <div class="rig-power-section">
                        <div class="power-indicator">
                            <span class="power-led"></span>
                            <span class="power-label">PWR / READY</span>
                        </div>
                    </div>
                    <div class="hex-screw top-right"></div>
                </div>

                <!-- Upper Deck: LCD Display + VFO Tuning Dial + Controls -->
                <div class="rig-upper-deck">
                    <!-- Main LCD / TFT Transceiver Screen -->
                    <div class="lcd-screen-housing">
                        <div class="lcd-screen" id="lcd-screen">
                            <!-- LCD Top Header Bar -->
                            <div class="lcd-header-bar">
                                <span class="lcd-tag">NODE: <strong><?php echo htmlspecialchars($nodeNumber); ?></strong></span>
                                <?php if ($isInternalClient): ?>
                                   <span class="lcd-tag">IP: <strong><?php echo htmlspecialchars($nodeIp); ?></strong></span>
                                <?php else: ?>
                                   <span class="lcd-tag">&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                                <?php endif ?>
                                <span class="lcd-tag lcd-mem-highlight" id="lcd-mem-tag">VFO-A</span>
                                <span class="lcd-tag sys-chip sys-unknown" id="sys-ram" title="RAM Usage"><span class="sys-icon">&#129504;</span><span class="sys-val">--%</span></span>
                                <span class="lcd-tag sys-chip sys-unknown" id="sys-disk" title="Disk Usage"><span class="sys-icon">&#128190;</span><span class="sys-val">--%</span></span>
                                <span class="lcd-tag sys-chip sys-unknown" id="sys-cpu" title="CPU Load"><span class="sys-icon">&#9881;&#65039;</span><span class="sys-val">--%</span></span>
                                <span class="lcd-tag sys-chip sys-unknown" id="sys-temp" title="CPU Temperature"><span class="sys-icon">&#127777;</span><span class="sys-val">--&deg;C</span></span>
                            </div>

                            <!-- Main Frequency / Callsign Readout -->
                            <div class="lcd-main-readout">
                                <div class="lcd-callsign" id="lcd-callsign"><?php echo htmlspecialchars($nodeTitle); ?></div>
                                <div class="lcd-channel-meta">
                                    <span class="lcd-subtext" id="lcd-active-title">STANDBY</span>
                                    <span class="lcd-frequency" id="lcd-active-code">430.000.00</span>
                                </div>
                            </div>

                            <!-- S-Meter / RF Power Display -->
                            <div class="lcd-smeter-container">
                                <div class="smeter-labels">
                                    <span>S 1</span><span>3</span><span>5</span><span>7</span><span>9</span>
                                    <span class="smeter-peak">+20</span><span class="smeter-peak">+40</span><span class="smeter-peak">+60 dB</span>
                                </div>
                                <div class="smeter-bar" id="smeter-bar">
                                    <div class="smeter-segment active-low"></div>
                                    <div class="smeter-segment active-low"></div>
                                    <div class="smeter-segment active-low"></div>
                                    <div class="smeter-segment active-low"></div>
                                    <div class="smeter-segment active-mid"></div>
                                    <div class="smeter-segment active-mid"></div>
                                    <div class="smeter-segment active-mid"></div>
                                    <div class="smeter-segment active-high"></div>
                                    <div class="smeter-segment active-high"></div>
                                    <div class="smeter-segment active-peak"></div>
                                    <div class="smeter-segment active-peak"></div>
                                    <div class="smeter-segment active-peak"></div>
                                </div>
                            </div>

                            <!-- Status Flags / Annunciators -->
                            <div class="lcd-flags-bar">
                                <div class="lcd-flag flag-rx active" id="flag-rx">● RX</div>
                                <div class="lcd-flag flag-tx" id="flag-tx">● TX</div>
                                <!-- <div class="lcd-flag flag-sql">SQL</div> -->
                                <div class="lcd-flag flag-link active" id="flag-link">NET-LINK</div>
                                <div class="lcd-flag flag-asl active">ASL3</div>
                            </div>

                            <!-- Dynamic LCD Status Line -->
                            <div class="lcd-status-marquee">
                                <span class="marquee-icon">&gt;&gt;</span>
                                <span class="marquee-text" id="lcd-marquee">STANDBY // READY FOR COMMANDS</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Last Heard Display & Rotary Knobs -->
                    <div class="rig-vfo-deck">
                        <!-- Secondary LCD: Last Heard (replaces the decorative VFO dial) -->
                        <div class="lastheard-screen-housing">
                            <div class="lastheard-lcd" id="lastheard-lcd">
                                <div class="lastheard-header">
                                    <span>LAST HEARD</span>
                                    <span class="lh-count" id="lh-count">0</span>
                                </div>
                                <div class="lastheard-rows" id="lastheard-rows">
                                    <div class="lastheard-empty">NO ACTIVITY YET</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Lower Deck -->
                <div class="rig-keypad-deck">

                <!-- Buttons for Status, Bubble Map, ASL MON -->
                    <div class="buttons-grid link-buttons-row">
                            <div class="rig-key-socket">
                                    <a href="/aslmon3" target="_blank" rel="noopener noreferrer" class="rig-key btn-blue key-link" style="min-height:30px" id="hrg_dash" data-title="ASL MON" data-type="LINK">
                                        <div class="key-led led-blue"></div>
                                        <div class="key-inner">
                                            <div class="key-label">ASL MON</div>
                                        </div>
                                    </a>
                            </div>

                            <div class="rig-key-socket">
                                    <a href="https://stats.allstarlink.org/stats/<?php echo $nodeNumber; ?>/networkMap" target="_blank" rel="noopener noreferrer" class="rig-key btn-blue key-link" style="min-height:30px" id="bubble_map" data-title="Bubble Map" data-type="LINK">
                                        <div class="key-led led-blue"></div>
                                        <div class="key-inner">
                                            <div class="key-label">Bubble Map</div>
                                        </div>
                                    </a>
                            </div>

                            <div class="rig-key-socket">
                                    <a href="https://stats.allstarlink.org/nodeinfo.cgi?node=<?php echo $nodeNumber; ?>" target="_blank" rel="noopener noreferrer" class="rig-key btn-blue key-link" style="min-height:30px" id="allstar_status" data-title="Allstar Status" data-type="LINK">
                                        <div class="key-led led-blue"></div>
                                        <div class="key-inner">
                                            <div class="key-label">Allstar Status</div>
                                        </div>
                                    </a>
                            </div>
                        </div><p>&nbsp;</p>



                <!-- Keypad & Memory Channels (The Action Buttons) -->
                    <?php if ($isInternalClient): ?>
                    <div class="keypad-header">
                        <span class="keypad-title">MEMORY CHANNELS & FUNCTION KEYS</span>
                        <div class="speaker-grille">
                            <span></span><span></span><span></span><span></span><span></span>
                            <span></span><span></span><span></span><span></span><span></span>
                        </div>
                    </div>

                    <div class="buttons-grid">
                        <?php 
                        $channelIndex = 1;
                        foreach($buttons as $key => $btn): 
                            $colorClass = isset($btn['color']) ? 'btn-' . htmlspecialchars($btn['color']) : 'btn-blue';
                            $title = isset($btn['title']) ? $btn['title'] : $key;
                            $hasHref = isset($btn['href']);
                            $hasCmds = isset($btn['cmds']);
                            $cmdLabel = '';
                            if ($hasCmds) {
                                $cmdList = (array)$btn['cmds'];
                                $cmdLabel = implode(' ', $cmdList);
                            }
                            $chTag = sprintf("M%02d", $channelIndex++);
                        ?>
                            <div class="rig-key-socket<?php echo $socketClass; ?>">
                                <?php if ($hasHref): ?>
                                    <a href="<?php echo htmlspecialchars($btn['href']); ?>" target="_blank" rel="noopener noreferrer" class="rig-key <?php echo $colorClass; ?> key-link<?php echo $specialClass; ?>" id="<?php echo htmlspecialchars($key); ?>" data-title="<?php echo htmlspecialchars($title); ?>" data-type="LINK" data-ch="<?php echo $chTag; ?>">
                                        <div class="key-led led-<?php echo htmlspecialchars(isset($btn['color']) ? $btn['color'] : 'blue'); ?>"></div>
                                        <div class="key-inner">
                                            <div class="key-meta">
                                                <span class="key-ch">[<?php echo $chTag; ?>]</span>
                                                <span class="key-ext">EXT ↗</span>
                                            </div>
                                            <div class="key-label"><?php echo htmlspecialchars($title); ?></div>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="rig-key <?php echo $colorClass; ?> key-action" id="<?php echo htmlspecialchars($key); ?>" data-cmd="<?php echo htmlspecialchars($key); ?>" data-title="<?php echo htmlspecialchars($title); ?>" data-code="<?php echo htmlspecialchars($cmdLabel); ?>" data-ch="<?php echo $chTag; ?>">
                                        <div class="key-led led-<?php echo htmlspecialchars(isset($btn['color']) ? $btn['color'] : 'green'); ?>"></div>
                                        <div class="key-inner">
                                            <div class="key-meta">
                                                <span class="key-ch">[<?php echo $chTag; ?>]</span>
                                                <?php if (!empty($cmdLabel)): ?>
                                                    <span class="key-code"><?php echo htmlspecialchars($cmdLabel); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="key-label"><?php echo htmlspecialchars($title); ?></div>
                                        </div>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Manual Command: wolne wpisanie *NNNN lub cop NN, nie musi być w buttons.ini -->
                    <div class="manual-cmd-bar">
                        <span class="manual-cmd-label">MANUAL CMD:</span>
                        <input type="text" id="manual-cmd-input" class="manual-cmd-input"
                               placeholder="*365321 or cop 73" maxlength="24"
                               autocomplete="off" spellcheck="false">
                        <button type="button" id="manual-cmd-send" class="manual-cmd-send">SEND</button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Connected Nodes Deck -->
                <div class="rig-links-deck">
                  <div class="links-header">
                   <span class="links-title">CONNECTED NODES</span>
                   <span class="links-badges">
                      <span class="links-count links-onair" id="links-onair">0 ON AIR</span>
                      <span class="links-count" id="links-count">0 LINKED</span>
                      <span class="links-count" id="links-remote">0 REMOTE LINKS</span>
                    </span>
                    </div>
                    <div class="links-grid" id="links-grid">
                        <div class="links-empty">NO ACTIVE LINKS</div>
                    </div>
                    <div class="link-details-bar" id="link-details-bar">
                        <span class="ld-label">&gt;&gt;</span> Hover or tap a node to see details
                    </div>
                </div>

                <!-- Bottom Bezel -->
                <div class="rig-bottom-bar">
                    <div class="hex-screw bottom-left"></div>
                    <div class="rig-footer-text">BASE ON CODE <a style="color: yellow;" target=_blank href="https://github.com/andrewmacrides-web/M0FXB-HAMTECH-Andreas/">M0FXB</a> - <span style="color:cyan;">MOD SP2ONG</span> &bull; ALLSTARLINK NODE <?php echo htmlspecialchars($nodeNumber); ?></div>
                    <div class="hex-screw bottom-right"></div>
                </div>
            </div>
        </div>

        <div id="toast-container" class="toast-container" aria-live="polite"></div>

        <script>
            let currentVfoAngle = 0;
            const vfoDial = document.getElementById('vfo-dial');
            const lcdMarquee = document.getElementById('lcd-marquee');
            const lcdMemTag = document.getElementById('lcd-mem-tag');
            const lcdActiveTitle = document.getElementById('lcd-active-title');
            const lcdActiveCode = document.getElementById('lcd-active-code');
            const flagTx = document.getElementById('flag-tx');
            const flagRx = document.getElementById('flag-rx');
            const smeterSegments = document.querySelectorAll('.smeter-segment');
            const smeterBar = document.getElementById('smeter-bar');

            // Interactive VFO Dial Rotation
            function rotateVfo(delta) {
                currentVfoAngle = (currentVfoAngle + delta) % 360;
                vfoDial.style.transform = `rotate(${currentVfoAngle}deg)`;
            }

            if (vfoDial) {
                vfoDial.addEventListener('wheel', (e) => {
                    e.preventDefault();
                    rotateVfo(e.deltaY > 0 ? 15 : -15);
                });

                let isDragging = false;
                let startX = 0;
                vfoDial.addEventListener('mousedown', (e) => {
                    isDragging = true;
                    startX = e.clientX;
                });
                window.addEventListener('mousemove', (e) => {
                    if (isDragging) {
                        const delta = (e.clientX - startX) * 2;
                        rotateVfo(delta);
                        startX = e.clientX;
                    }
                });
                window.addEventListener('mouseup', () => { isDragging = false; });
            }

            function showToast(message, type = 'info') {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                toast.innerHTML = `<span class="toast-icon">${type === 'success' ? '✓' : (type === 'error' ? '✕' : 'ℹ')}</span> <span class="toast-message">${message}</span>`;
                container.appendChild(toast);

                setTimeout(() => { toast.classList.add('toast-show'); }, 10);
                setTimeout(() => {
                    toast.classList.remove('toast-show');
                    setTimeout(() => {
                        if (toast.parentNode) toast.parentNode.removeChild(toast);
                    }, 300);
                }, 3000);
            }

            // Update LCD Display based on clicked channel
            function updateLcdChannel(title, code, chTag, cmd) {
                // Flash Transmit State
                flagRx.classList.remove('active');
                flagTx.classList.add('active');
                smeterBar.classList.remove('smeter-idle', 'smeter-rx');
                smeterBar.classList.add('smeter-tx');

                // Immediate Transmit Announcement
                lcdMarquee.innerText = `TX: [${chTag}] ${code ? '[' + code + '] ' : ''}${title.toUpperCase()} DISPATCHED`;
                lcdMarquee.style.color = '#ff4444';

                // Update Main Display Readouts
                if (cmd === 'disconnect_all' || code === '*73') {
                    lcdMemTag && (lcdMemTag.innerText = 'STANDBY');
                    lcdActiveTitle.innerText = 'DISCONNECTED';
                    lcdActiveTitle.style.color = '#ff4444';
                    lcdActiveCode.innerText = 'NO ACTIVE LINKS';

                    // Clear active selection highlights
                    document.querySelectorAll('.rig-key').forEach(k => k.classList.remove('key-selected'));
                } else {
                    lcdMemTag && (lcdMemTag.innerText = `CH: ${chTag}`);
                    lcdActiveTitle.innerText = title.toUpperCase();
                    lcdActiveTitle.style.color = '#ffb300';
                    lcdActiveCode.innerText = code ? `DTMF: ${code}` : 'ACTIVE';
                }

                // Settle back to active state after transmit burst
                setTimeout(() => {
                    flagTx.classList.remove('active');
                    flagRx.classList.add('active');
                    smeterBar.classList.remove('smeter-tx');

                    if (cmd === 'disconnect_all' || code === '*73') {
                        lcdMarquee.innerText = 'STANDBY // ALL RF LINKS DISCONNECTED';
                        lcdMarquee.style.color = '#00e5ff';
                    } else {
                        lcdMarquee.innerText = `ONLINE // CONNECTED TO ${title.toUpperCase()}`;
                        lcdMarquee.style.color = '#00e5ff';
                    }
                }, 1400);
            }

            document.querySelectorAll('.key-action').forEach(btn => {
                btn.addEventListener('click', function () {
                    const cmd = this.getAttribute('data-cmd');
                    const title = this.getAttribute('data-title');
                    const code = this.getAttribute('data-code');
                    const chTag = this.getAttribute('data-ch');

                    // Button depression haptics
                    this.classList.add('key-pressed');
                    setTimeout(() => this.classList.remove('key-pressed'), 250);

                    // Set persistent selected state on the key
                    if (cmd !== 'disconnect_all' && code !== '*73') {
                        document.querySelectorAll('.rig-key').forEach(k => k.classList.remove('key-selected'));
                        this.classList.add('key-selected');
                    }

                    updateLcdChannel(title, code, chTag, cmd);
                    showToast(`Sending: [${chTag}] ${title} ${code ? '(' + code + ')' : ''}`, 'info');

                    fetch(`control.php?cmd=${encodeURIComponent(cmd)}`)
                        .then(response => {
                            if (response.ok) {
                                showToast(`Executed: ${title}`, 'success');
                            } else {
                                showToast(`Error executing: ${title}`, 'error');
                            }
                        })
                        .catch(err => {
                            showToast(`Failed to dispatch command to Asterisk`, 'error');
                        });
                });
            });

            /* ============================================================
             * Manual Command — wolny wpis *NNNN lub cop NN.
             * Walidacja jest tu TYLKO dla szybkiego feedbacku - prawdziwe
             * zabezpieczenie (biała lista regex) jest po stronie control.php,
             * bo to jedyne miejsce, któremu można zaufać.
             * ============================================================ */
            const manualCmdInput = document.getElementById('manual-cmd-input');
            const manualCmdSend  = document.getElementById('manual-cmd-send');
            const MANUAL_CMD_PATTERN = /^(\*[0-9]{1,20}|cop[:\s]*[0-9]{1,3})$/i;

            function sendManualCmd() {
                if (!manualCmdInput) return;
                const raw = manualCmdInput.value.trim();
                if (!raw) return;

                if (!MANUAL_CMD_PATTERN.test(raw)) {
                    showToast(`Invalid format - use *NNNN or cop NN`, 'error');
                    return;
                }

                showToast(`Sending manual: ${raw}`, 'info');
                fetch(`control.php?cmd=manual&code=${encodeURIComponent(raw)}`)
                    .then(response => {
                        if (response.ok) {
                            showToast(`Executed: ${raw}`, 'success');
                            manualCmdInput.value = '';
                        } else {
                            showToast(`Rejected by server: ${raw}`, 'error');
                        }
                    })
                    .catch(() => showToast(`Failed to dispatch manual command`, 'error'));
            }

            if (manualCmdSend) {
                manualCmdSend.addEventListener('click', sendManualCmd);
            }
            if (manualCmdInput) {
                manualCmdInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') sendManualCmd();
                });
            }

            /* ============================================================
             * Live status polling — Connected Nodes / Last Heard / LCD
             * Odpytuje status.php (tylko czyta state.json, nie woła
             * Asteriska bezpośrednio) co POLL_MS milisekund.
             * ============================================================ */
            const POLL_MS = 2000;

            const linksGrid       = document.getElementById('links-grid');
            const linksCount      = document.getElementById('links-count');
            const linksOnAir      = document.getElementById('links-onair');
            const linksRemote     = document.getElementById('links-remote');
            const linkDetailsBar  = document.getElementById('link-details-bar');
            const lastheardRows   = document.getElementById('lastheard-rows');
            const lhCount         = document.getElementById('lh-count');
            const offlineBanner   = document.getElementById('rpt-offline-banner');
            const sysCpu  = document.getElementById('sys-cpu');
            const sysRam  = document.getElementById('sys-ram');
            const sysDisk = document.getElementById('sys-disk');
            const sysTemp = document.getElementById('sys-temp');

            function fmtHMS(totalSeconds) {
                totalSeconds = Math.max(0, totalSeconds | 0);
                const h = Math.floor(totalSeconds / 3600);
                const m = Math.floor((totalSeconds % 3600) / 60);
                const s = totalSeconds % 60;
                return [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
            }

            function fmtClock(unixTs) {
                return new Date(unixTs * 1000).toLocaleTimeString([], {
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
            }

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str == null ? '' : String(str);
                return div.innerHTML;
            }

            function linkStateClass(link) {
                if (link.keyed) return 'state-keyed';
                if (link.state !== 'ESTABLISHED') return 'state-connecting';
                if (link.mode === 'R') return 'state-monitor';
                return 'state-transceive';
            }

            function showLinkDetails(link) {
                const parts = [
                    `NODE ${link.node}`,
                    link.callsign || 'UNKNOWN',
                    link.desc || '',
                    link.loc || '',
                    `${link.dir} / ${link.state}`,
                    `UP ${fmtHMS(link.uptime)}`
                ].filter(Boolean).map(escapeHtml);
                linkDetailsBar.innerHTML = `<span class="ld-label">&gt;&gt;</span> ${parts.join(' &bull; ')}`;
            }

function renderLinks(links) {
    linksCount.textContent = `${links.length} LINKED`;

    const onAirCount = links.filter(l => l.keyed).length;
    linksOnAir.textContent = `${onAirCount} ON AIR`;
    linksOnAir.classList.toggle('active', onAirCount > 0);

    if (!links.length) {
                    linksGrid.innerHTML = '<div class="links-empty">NO ACTIVE LINKS</div>';
                    linkDetailsBar.innerHTML = '<span class="ld-label">&gt;&gt;</span> Hover or tap a node to see details';
                    return;
                }

                linksGrid.innerHTML = '';
                links.forEach((link, i) => {
                    const el = document.createElement('div');
                    el.className = `rig-key link-key ${linkStateClass(link)}`;
                    el.tabIndex = 0;
                    el.innerHTML = `
                        <div class="key-led ${link.keyed ? 'keyed' : ''}"></div>
                        <div class="key-inner">
                            <div class="key-meta">
                                <span class="key-ch">[L${String(i + 1).padStart(2, '0')}]</span>
                                <span class="key-code">${escapeHtml(link.dir)}</span>
                            </div>
                            <div class="key-label">${escapeHtml(link.node)}${link.callsign ? ' &middot; ' + escapeHtml(link.callsign) : ''}</div>
                        </div>
                    `;
                    el.addEventListener('mouseenter', () => showLinkDetails(link));
                    el.addEventListener('focus', () => showLinkDetails(link));
                    el.addEventListener('click', () => showLinkDetails(link));
                    linksGrid.appendChild(el);
                });
            }

            function renderLastHeard(entries) {
                lhCount.textContent = entries.length;

                if (!entries.length) {
                    lastheardRows.innerHTML = '<div class="lastheard-empty">NO ACTIVITY YET</div>';
                    return;
                }

                lastheardRows.innerHTML = entries.map(e => `
                    <div class="lastheard-row">
                        <span class="lh-time">${fmtClock(e.t)}</span>
                        <span class="lh-node">${escapeHtml(e.node)}</span>
                        <span class="lh-call">${escapeHtml(e.callsign || 'Unknown')}</span>
                        <span class="lh-dur">${e.dur}s</span>
                    </div>
                `).join('');
            }

            // Ożywienie głównego LCD prawdziwym stanem z kolektora.
            // Nie nadpisuje flag w trakcie 1.4s animacji "TX DISPATCHED"
            // wywołanej kliknięciem klawisza (patrz updateLcdChannel powyżej).
            let manualFlashUntil = 0;

            function updateMainLcdFromStatus(data) {
                if (Date.now() < manualFlashUntil) return;

flagRx.classList.toggle('active', !!data.cos);
flagTx.classList.toggle('active', !!data.ptt);

smeterBar.classList.remove('smeter-idle', 'smeter-rx', 'smeter-tx');
if (data.ptt) {
    smeterBar.classList.add('smeter-tx');
} else if (data.cos) {
    smeterBar.classList.add('smeter-rx');
} else {
    smeterBar.classList.add('smeter-idle');
}


                if (data.links && data.links.length) {
                    const active = data.links.find(l => l.keyed) || data.links[0];
                    lcdMemTag && (lcdMemTag.innerText = 'VFO-A');
                    lcdActiveTitle.innerText = active.keyed ? `RX: ${active.callsign || active.node}` : 'STANDBY';
                    lcdActiveTitle.style.color = active.keyed ? '#ff4444' : '#ffb300';
                    lcdActiveCode.innerText = `NODE ${active.node}`;
                    lcdMarquee.innerText = active.keyed
                        ? `RX: ${(active.callsign || 'UNKNOWN').toUpperCase()} (${active.node}) KEYED`
                        : `ONLINE // ${data.links.length} LINK(S) CONNECTED`;
                    lcdMarquee.style.color = '#00e5ff';
                } else {
                    lcdMemTag && (lcdMemTag.innerText = 'VFO-A');
                    lcdActiveTitle.innerText = 'STANDBY';
                    lcdActiveTitle.style.color = '#ffb300';
                    lcdActiveCode.innerText = 'NO ACTIVE LINKS';
                    lcdMarquee.innerText = 'STANDBY // READY FOR COMMANDS';
                    lcdMarquee.style.color = '#00e5ff';
                }
            }

            // Wywoływane z updateLcdChannel przy kliknięciu klawisza,
            // żeby polling nie nadpisał animacji w trakcie jej trwania.
            const _origUpdateLcdChannel = updateLcdChannel;
            updateLcdChannel = function (title, code, chTag, cmd) {
                manualFlashUntil = Date.now() + 1500;
                _origUpdateLcdChannel(title, code, chTag, cmd);
            };

            // Progi ostrzegawcze dopasowane pod Raspberry Pi (throttling ~80°C).
            function sysLevelClass(value, warnAt, critAt) {
                if (value === null || value === undefined) return 'sys-unknown';
                if (value >= critAt) return 'sys-crit';
                if (value >= warnAt) return 'sys-warn';
                return 'sys-ok';
            }

            function setSysChip(el, value, unit, warnAt, critAt) {
                const valEl = el.querySelector('.sys-val');
                valEl.textContent = (value === null || value === undefined)
                    ? '--' + unit
                    : value + unit;
                el.classList.remove('sys-ok', 'sys-warn', 'sys-crit', 'sys-unknown');
                el.classList.add(sysLevelClass(value, warnAt, critAt));
            }

            function renderSys(sys) {
                if (!sys) return;
                setSysChip(sysCpu,  sys.cpu_pct,  '%', 60, 85);
                setSysChip(sysRam,  sys.mem_pct,  '%', 70, 90);
                setSysChip(sysDisk, sys.disk_pct, '%', 70, 90);
                setSysChip(sysTemp, sys.temp_c,  '°C', 60, 75);
            }

            async function pollStatus() {
                try {
                    const res = await fetch('status.php', { cache: 'no-store' });
                    const data = await res.json();

                    offlineBanner.classList.toggle('show', data.rpt_ok === false);

                    renderLinks(data.links || []);
                    linksRemote.textContent = `${data.numlinks || 0} REMOTE LINKS`;
                    renderLastHeard(data.lastheard || []);
                    renderSys(data.sys);
                    updateMainLcdFromStatus(data);
                } catch (err) {
                    offlineBanner.classList.add('show');
                }
            }

            pollStatus();
            setInterval(pollStatus, POLL_MS);
        </script>
    </body>
</html>
