/**
 * NetworkTrouble - Interactive Network Topology Simulation
 * Unified state management, standardized node statuses,
 * dynamic Bootstrap tooltips, and consistent diagnosis mapping.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Unified Status System & Helper Functions
    const STATUS_CONFIG = {
        healthy: {
            code: 'healthy',
            label: 'Healthy',
            color: '#10b981',
            fill: '#f0fdf4',
            stroke: '#10b981',
            badgeClass: 'bg-success-subtle text-success border border-success-subtle',
            dotClass: 'pulse-dot'
        },
        warning: {
            code: 'warning',
            label: 'Warning',
            color: '#f59e0b',
            fill: '#fffbeb',
            stroke: '#f59e0b',
            badgeClass: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            dotClass: 'pulse-dot pulse-warning'
        },
        critical: {
            code: 'critical',
            label: 'Critical / Error',
            color: '#ef4444',
            fill: '#fef2f2',
            stroke: '#ef4444',
            badgeClass: 'bg-danger-subtle text-danger border border-danger-subtle',
            dotClass: 'pulse-dot pulse-danger'
        },
        unknown: {
            code: 'unknown',
            label: 'Unknown',
            color: '#94a3b8',
            fill: '#f8fafc',
            stroke: '#94a3b8',
            badgeClass: 'bg-secondary-subtle text-secondary border',
            dotClass: 'pulse-dot'
        }
    };

    function normalizeStatus(status) {
        if (!status) return 'unknown';
        const s = String(status).toLowerCase();
        if (s === 'healthy' || s === 'ok' || s === 'normal') return 'healthy';
        if (s === 'warning' || s === 'warn' || s === 'degraded') return 'warning';
        if (s === 'critical' || s === 'error' || s === 'problem' || s === 'problem detected' || s === 'down' || s === 'disconnected') return 'critical';
        return 'unknown';
    }

    // Standard single helper functions required
    window.getNodeStatusClass = function(status) {
        const norm = normalizeStatus(status);
        return STATUS_CONFIG[norm].badgeClass;
    };

    window.getNodeStatusColor = function(status) {
        const norm = normalizeStatus(status);
        return STATUS_CONFIG[norm].stroke;
    };

    window.getNodeStatusLabel = function(status) {
        const norm = normalizeStatus(status);
        return STATUS_CONFIG[norm].label;
    };

    // 2. Network Topology Base Nodes Definition
    const TOPOLOGY_NODES = {
        'client-pc': {
            id: 'client-pc',
            name: 'Client 1 (Workstation PC)',
            icon: '💻',
            defaultRole: 'Workstation endpoint berkabel Ethernet LAN.',
            layer: 'Layer 1-3 (Physical / Data Link / Network)',
            defaultIp: '192.168.1.15',
            description: 'Perangkat workstation endpoint pengguna yang mengirim dan menerima paket data melalui interface Ethernet kabel LAN.',
            defaultCli: [
                { cmd: 'ipconfig /all', desc: 'Periksa seluruh konfigurasi adapter, MAC address, IP, gateway, dan DHCP server' },
                { cmd: 'ipconfig /release && ipconfig /renew', desc: 'Minta alokasi alamat IP baru dari DHCP router' }
            ]
        },
        'laptop': {
            id: 'laptop',
            name: 'Client 2 (Laptop Workstation)',
            icon: '💻',
            defaultRole: 'Perangkat pembanding dalam jaringan lokal.',
            layer: 'Layer 1-3 (Physical / Data Link / Network)',
            defaultIp: '192.168.1.25',
            description: 'Perangkat endpoint sekunder yang terhubung melalui kabel Ethernet ke Switch L2 distribusi.',
            defaultCli: [
                { cmd: 'ping 192.168.1.15', desc: 'Uji konektivitas internal peer-to-peer antar workstation' },
                { cmd: 'ipconfig /all', desc: 'Periksa alokasi gateway dan DNS yang diterima dari DHCP' }
            ]
        },
        'smartphone': {
            id: 'smartphone',
            name: 'Client 3 (Smartphone WiFi)',
            icon: '📱',
            defaultRole: 'Perangkat nirkabel via Access Point / WiFi.',
            layer: 'Layer 1-2 (Wireless / PHY 802.11ac)',
            defaultIp: '192.168.1.50',
            description: 'Perangkat endpoint nirkabel yang terhubung menggunakan gelombang radio 5 GHz ke router/AP.',
            defaultCli: [
                { cmd: 'netsh wlan show interfaces', desc: 'Tampilkan status adapter WiFi, RSSI sinyal (%), dan tipe radio' },
                { cmd: 'netsh wlan show networks mode=bssid', desc: 'Periksa kanal frekuensi dan access point terdekat' }
            ]
        },
        'switch': {
            id: 'switch',
            name: 'Switch L2 (Distribution)',
            icon: '🔀',
            defaultRole: 'Konsentrator frame jaringan lokal.',
            layer: 'Layer 2 (Data Link Layer)',
            defaultIp: 'VLAN 10 (Layer 2)',
            description: 'Konsentrator kabel yang meneruskan frame ethernet antar perangkat lokal berdasarkan tabel MAC Address.',
            defaultCli: [
                { cmd: 'arp -a', desc: 'Tampilkan tabel pemetaan IP ke MAC address pada jaringan lokal' },
                { cmd: 'netsh interface ipv4 show subinterfaces', desc: 'Periksa MTU dan link speed interface' }
            ]
        },
        'gateway': {
            id: 'gateway',
            name: 'Gateway Router',
            icon: '🌐',
            defaultRole: 'Jalur keluar jaringan lokal, DHCP & NAT.',
            layer: 'Layer 3 (Network Layer)',
            defaultIp: '192.168.1.1',
            description: 'Perangkat perantara yang mengarahkan paket (routing), mengelola alokasi alamat IP dinamis (DHCP), dan Network Address Translation (NAT).',
            defaultCli: [
                { cmd: 'ping -n 4 192.168.1.1', desc: 'Uji konektivitas ICMP ke default gateway lokal' },
                { cmd: 'tracert -d 8.8.8.8', desc: 'Lacak lompatan paket hop pertama menuju router gateway' }
            ]
        },
        'dns': {
            id: 'dns',
            name: 'DNS Resolver (8.8.8.8)',
            icon: '🗄️',
            defaultRole: 'Translasi nama domain ke IP Address.',
            layer: 'Layer 7 (Application Layer)',
            defaultIp: '8.8.8.8',
            description: 'Server yang mengonversi nama host domain (seperti google.com) menjadi alamat IP biner numerik.',
            defaultCli: [
                { cmd: 'nslookup google.com 8.8.8.8', desc: 'Uji apakah DNS server mampu memetakan domain' },
                { cmd: 'ipconfig /flushdns', desc: 'Bersihkan cache DNS lokal komputer' }
            ]
        },
        'cloud': {
            id: 'cloud',
            name: 'WAN / Cloud (ISP Upstream)',
            icon: '☁️',
            defaultRole: 'Penyedia akses internet publik & routing global.',
            layer: 'Cross-Layer (Transport & QoS / WAN)',
            defaultIp: 'Public Internet WAN',
            description: 'Infrastruktur penyedia layanan internet (ISP) yang menghubungkan jaringan lokal ke internet global.',
            defaultCli: [
                { cmd: 'ping -n 20 8.8.8.8', desc: 'Uji 20 paket ping untuk memeriksa kestabilan latency dan packet loss' },
                { cmd: 'tracert 8.8.8.8', desc: 'Lacak hop router eksternal yang mengalami lonjakan latensi' }
            ]
        }
    };

    // Connecting lines map
    const NODE_LINES_MAP = {
        'client-pc': ['line-pc-switch'],
        'laptop': ['line-laptop-switch'],
        'smartphone': ['line-wifi-router'],
        'switch': ['line-pc-switch', 'line-laptop-switch', 'line-switch-router'],
        'gateway': ['line-switch-router', 'line-wifi-router', 'line-router-cloud', 'line-router-dns'],
        'dns': ['line-router-dns'],
        'cloud': ['line-router-cloud']
    };

    // 3. Full Simulation Scenarios Mapping with Single Source of Truth
    const SCENARIOS = {
        'healthy': {
            title: 'Normal (All Healthy)',
            badgeHtml: '<i class="bi bi-check-circle-fill text-success me-1"></i>Simulasi Normal (All Healthy)',
            pills: {
                cable: '<span class="badge bg-success-subtle text-success border border-success-subtle">Connected (Link OK)</span>',
                dhcp: '<span class="badge bg-success-subtle text-success border border-success-subtle">Valid Lease (192.168.1.15)</span>',
                dns: '<span class="badge bg-success-subtle text-success border border-success-subtle">Responding (18 ms)</span>'
            },
            affectedLines: {},
            nodes: {
                'client-pc': { status: 'healthy', status_label: 'Healthy', message: 'Koneksi Ethernet normal, IP valid diterima dari DHCP.', ip: '192.168.1.15' },
                'laptop': { status: 'healthy', status_label: 'Healthy', message: 'Koneksi LAN normal, link speed 1 Gbps.', ip: '192.168.1.25' },
                'smartphone': { status: 'healthy', status_label: 'Healthy', message: 'Koneksi WiFi 5GHz stabil, RSSI -52 dBm.', ip: '192.168.1.50' },
                'switch': { status: 'healthy', status_label: 'Healthy', message: 'Switch L2 aktif, port link auto-negotiation 1000/Full.', ip: 'VLAN 10' },
                'gateway': { status: 'healthy', status_label: 'Healthy', message: 'Router gateway aktif, routing NAT & DHCP melayani permintaan.', ip: '192.168.1.1' },
                'dns': { status: 'healthy', status_label: 'Healthy', message: 'DNS Resolver merespons kueri domain dengan latency rendah.', ip: '8.8.8.8' },
                'cloud': { status: 'healthy', status_label: 'Healthy', message: 'Koneksi ISP upstream aktif, packet loss 0%.', ip: 'Public WAN' }
            }
        },

        'physical': {
            title: 'Physical Layer (Kabel Putus / Link Down)',
            badgeHtml: '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Physical Layer (Kabel LAN Putus / Port Mati)',
            pills: {
                cable: '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Disconnected (LED Off)</span>',
                dhcp: '<span class="badge bg-secondary-subtle text-secondary border">No Media Link</span>',
                dns: '<span class="badge bg-secondary-subtle text-secondary border">Unreachable</span>'
            },
            affectedLines: { 'line-pc-switch': 'line-problem' },
            nodes: {
                'client-pc': {
                    status: 'critical',
                    status_label: 'Media Disconnected',
                    message: 'Kabel LAN tidak terdeteksi atau konektor RJ45 terlepas dari port adapter PC.',
                    ip: 'No Media Link',
                    possible_causes: 'Kabel UTP putus/tertekuk, konektor RJ45 patah, atau adapter LAN dinonaktifkan.',
                    cli_commands: [
                        { cmd: 'ncpa.cpl', desc: 'Buka Network Connections untuk memastikan adapter Ethernet aktif' },
                        { cmd: 'ipconfig', desc: 'Cek apakah adapter berstatus "Media disconnected"' }
                    ]
                },
                'switch': {
                    status: 'warning',
                    status_label: 'Port Link Down',
                    message: 'Port 1 switch tidak mendeteksi sinyal link carrier dari Client 1.',
                    ip: 'VLAN 10'
                },
                'laptop': { status: 'healthy', status_label: 'Healthy', message: 'Client pembanding normal, membuktikan switch tidak mati total.' },
                'smartphone': { status: 'healthy', status_label: 'Healthy', message: 'Koneksi WiFi tetap berjalan normal.' },
                'gateway': { status: 'healthy', status_label: 'Healthy', message: 'Gateway normal melayani klien lain.' },
                'dns': { status: 'healthy', status_label: 'Healthy', message: 'DNS resolver online.' },
                'cloud': { status: 'healthy', status_label: 'Healthy', message: 'ISP WAN normal.' }
            }
        },

        'network': {
            title: 'Network Layer (DHCP APIPA 169.254)',
            badgeHtml: '<i class="bi bi-diagram-3-fill text-warning me-1"></i>Network Layer (DHCP Lease Gagal / IP APIPA 169.254)',
            pills: {
                cable: '<span class="badge bg-success-subtle text-success border border-success-subtle">Connected (Link OK)</span>',
                dhcp: '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">APIPA (169.254.120.4)</span>',
                dns: '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Gateway Unreachable</span>'
            },
            affectedLines: { 'line-pc-switch': 'line-warning' },
            nodes: {
                'client-pc': {
                    status: 'warning',
                    status_label: 'APIPA Address (169.254.x.x)',
                    message: 'Client 1 tidak memperoleh IP dari server DHCP sehingga memakai alamat darurat APIPA 169.254.120.4.',
                    ip: '169.254.120.4',
                    possible_causes: 'DHCP Pool penuh, router DHCP daemon macet, atau port switch berada pada VLAN salah.',
                    cli_commands: [
                        { cmd: 'ipconfig /all', desc: 'Tinjau Autoconfiguration IPv4 Address (169.254.x.x)' },
                        { cmd: 'ipconfig /release && ipconfig /renew', desc: 'Paksa permintaan sewa IP baru ke DHCP Server' }
                    ]
                },
                'switch': { status: 'healthy', status_label: 'Healthy', message: 'Layer 2 forwarding frame Ethernet normal.' },
                'gateway': { status: 'healthy', status_label: 'Healthy', message: 'Router gateway beroperasi, DHCP pool memerlukan pengecekan.' },
                'dns': { status: 'healthy', status_label: 'Healthy', message: 'DNS Resolver publik normal.' },
                'laptop': { status: 'healthy', status_label: 'Healthy', message: 'Client pembanding memegang lease IP valid.' },
                'smartphone': { status: 'healthy', status_label: 'Healthy', message: 'Klien WiFi tetap terhubung normal.' },
                'cloud': { status: 'healthy', status_label: 'Healthy', message: 'Upstream WAN normal.' }
            }
        },

        'dns': {
            title: 'Application Layer (DNS Resolver Down)',
            badgeHtml: '<i class="bi bi-globe2 text-danger me-1"></i>Application Layer (DNS Resolver Down / NXDOMAIN)',
            pills: {
                cable: '<span class="badge bg-success-subtle text-success border border-success-subtle">Connected</span>',
                dhcp: '<span class="badge bg-success-subtle text-success border border-success-subtle">Valid Lease (192.168.1.15)</span>',
                dns: '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">DNS_PROBE_FINISHED_NXDOMAIN</span>'
            },
            affectedLines: { 'line-router-dns': 'line-problem' },
            nodes: {
                'dns': {
                    status: 'critical',
                    status_label: 'DNS Resolver Down',
                    message: 'DNS server tidak merespons kueri resolusi domain. Ping ke IP berhasil, tetapi domain gagal dibuka.',
                    ip: '8.8.8.8 (Timeout)',
                    possible_causes: 'Server DNS utama down, cache DNS lokal korup, atau firewall port 53 UDP diblokir.',
                    cli_commands: [
                        { cmd: 'ipconfig /flushdns', desc: 'Bersihkan cache DNS lokal komputer' },
                        { cmd: 'nslookup google.com 8.8.8.8', desc: 'Uji resolusi langsung ke resolver alternatif' },
                        { cmd: 'ping 8.8.8.8', desc: 'Verifikasi rute IP ke alamat DNS masih terbuka' }
                    ]
                },
                'client-pc': { status: 'healthy', status_label: 'Healthy (Network OK)', message: 'Konektivitas IP lokal & gateway normal, hanya resolusi nama domain yang terhambat.' },
                'gateway': { status: 'healthy', status_label: 'Healthy', message: 'Routing level IP berjalan normal.' },
                'switch': { status: 'healthy', status_label: 'Healthy', message: 'Layer 2 switching normal.' },
                'laptop': { status: 'healthy', status_label: 'Healthy (Network OK)', message: 'Jalur jaringan normal.' },
                'smartphone': { status: 'healthy', status_label: 'Healthy', message: 'WiFi normal.' },
                'cloud': { status: 'healthy', status_label: 'Healthy', message: 'ISP WAN normal.' }
            }
        },

        'wireless': {
            title: 'Wireless Issue (Sinyal WiFi Drop)',
            badgeHtml: '<i class="bi bi-wifi text-warning me-1"></i>Wireless Issue (Sinyal WiFi Drop / RSSI Rendah)',
            pills: {
                cable: '<span class="badge bg-success-subtle text-success border border-success-subtle">LAN OK (Connected)</span>',
                dhcp: '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">WiFi Packet Retries</span>',
                dns: '<span class="badge bg-success-subtle text-success border border-success-subtle">Responding</span>'
            },
            affectedLines: { 'line-wifi-router': 'line-warning' },
            nodes: {
                'smartphone': {
                    status: 'warning',
                    status_label: 'Weak Signal / Drops',
                    message: 'Kekuatan sinyal WiFi sangat rendah (RSSI < -82 dBm) menyebabkan retransmisi paket tinggi dan disconnect acak.',
                    ip: '192.168.1.50',
                    possible_causes: 'Jarak terlalu jauh dari Access Point, terhalang beton, atau interferensi kanal frekuensi 2.4/5 GHz.',
                    cli_commands: [
                        { cmd: 'netsh wlan show interfaces', desc: 'Cek Signal strength (%) dan channel frekuensi yang dipakai' },
                        { cmd: 'netsh wlan show networks mode=bssid', desc: 'Pindai AP sekitar dan periksa level noise sekitar' }
                    ]
                },
                'client-pc': { status: 'healthy', status_label: 'Healthy', message: 'Klien kabel LAN normal tidak terpengaruh sinyal nirkabel.' },
                'switch': { status: 'healthy', status_label: 'Healthy', message: 'Distribusi kabel LAN normal.' },
                'gateway': { status: 'healthy', status_label: 'Healthy', message: 'Radio AP memancarkan sinyal, namun klien di luar batas jangkauan ideal.' },
                'dns': { status: 'healthy', status_label: 'Healthy', message: 'DNS normal.' },
                'cloud': { status: 'healthy', status_label: 'Healthy', message: 'Koneksi ISP normal.' },
                'laptop': { status: 'healthy', status_label: 'Healthy', message: 'Laptop LAN normal.' }
            }
        },

        'performance': {
            title: 'Performance Issue (High Latency & Packet Loss)',
            badgeHtml: '<i class="bi bi-speedometer2 text-warning me-1"></i>Performance Issue (High Latency WAN & Packet Loss)',
            pills: {
                cable: '<span class="badge bg-success-subtle text-success border border-success-subtle">Connected (Link OK)</span>',
                dhcp: '<span class="badge bg-success-subtle text-success border border-success-subtle">Valid Lease</span>',
                dns: '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">High Latency (240 ms)</span>'
            },
            affectedLines: { 'line-router-cloud': 'line-warning' },
            nodes: {
                'cloud': {
                    status: 'warning',
                    status_label: 'High Latency / Loss',
                    message: 'Tingginya Round-Trip Time (>240ms) dan packet loss 12% pada sambungan upstream ISP.',
                    ip: 'Public WAN',
                    possible_causes: 'Kongesti bandwidth ISP, kabel fiber optik luar berredaman tinggi, atau throttling ISP.',
                    cli_commands: [
                        { cmd: 'ping -n 20 8.8.8.8', desc: 'Uji packet loss dan fluktuasi jitter ke host publik' },
                        { cmd: 'tracert -d 8.8.8.8', desc: 'Identifikasi hop gateway ISP yang mengalami lonjakan latensi' }
                    ]
                },
                'client-pc': { status: 'healthy', status_label: 'Healthy (LAN OK)', message: 'Jaringan lokal cepat (<1ms), kelambatan bersumber dari luar (WAN).' },
                'gateway': { status: 'healthy', status_label: 'Healthy', message: 'Router lokal memproses paket normal tanpa beban CPU berlebih.' },
                'switch': { status: 'healthy', status_label: 'Healthy', message: 'Switching lokal lancar tanpa drop frame.' },
                'dns': { status: 'warning', status_label: 'Slow Response', message: 'Waktu respons DNS melambat akibat latensi jaringan luar.' },
                'laptop': { status: 'healthy', status_label: 'Healthy', message: 'Koneksi LAN normal.' },
                'smartphone': { status: 'healthy', status_label: 'Healthy', message: 'WiFi normal.' }
            }
        },

        'security': {
            title: 'Security (IP Conflict & ARP Issue)',
            badgeHtml: '<i class="bi bi-shield-exclamation text-danger me-1"></i>Security Issue (IP Conflict / ARP Spoofing)',
            pills: {
                cable: '<span class="badge bg-success-subtle text-success border border-success-subtle">Connected</span>',
                dhcp: '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Duplicate IP Alert</span>',
                dns: '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Unstable</span>'
            },
            affectedLines: { 'line-pc-switch': 'line-warning', 'line-switch-router': 'line-problem' },
            nodes: {
                'client-pc': {
                    status: 'critical',
                    status_label: 'IP Conflict Detected',
                    message: 'Alamat IP 192.168.1.15 telah digunakan perangkat lain dalam segmen lokal yang sama.',
                    ip: '192.168.1.15 (Duplicated)',
                    possible_causes: 'Ada perangkat menyetel IP statis bentrok dengan alokasi DHCP atau indikasi ARP poisoning.',
                    cli_commands: [
                        { cmd: 'arp -a', desc: 'Inspeksi tabel ARP untuk mendeteksi duplikasi MAC Address' },
                        { cmd: 'ipconfig /renew', desc: 'Minta sewa alamat IP yang baru dan unik' }
                    ]
                },
                'gateway': {
                    status: 'warning',
                    status_label: 'ARP Table Flapping',
                    message: 'Router mencatat perubahan cepat pada pemetaan MAC address untuk IP 192.168.1.15.',
                    ip: '192.168.1.1'
                },
                'switch': { status: 'healthy', status_label: 'Healthy', message: 'Switch meneruskan frame sesuai MAC address table.' },
                'laptop': { status: 'healthy', status_label: 'Healthy', message: 'Laptop memegang IP berbeda yang tidak bentrok.' },
                'smartphone': { status: 'healthy', status_label: 'Healthy', message: 'WiFi normal.' },
                'dns': { status: 'healthy', status_label: 'Healthy', message: 'DNS normal.' },
                'cloud': { status: 'healthy', status_label: 'Healthy', message: 'WAN normal.' }
            }
        },

        'gateway_down': {
            title: 'Gateway Down (Default Gateway Unreachable)',
            badgeHtml: '<i class="bi bi-exclamation-octagon-fill text-danger me-1"></i>Critical: Gateway Router Down (No Route to WAN)',
            pills: {
                cable: '<span class="badge bg-success-subtle text-success border border-success-subtle">Connected (Link OK)</span>',
                dhcp: '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">No Gateway Response</span>',
                dns: '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Unreachable</span>'
            },
            affectedLines: { 'line-switch-router': 'line-problem', 'line-router-cloud': 'line-problem', 'line-router-dns': 'line-problem' },
            nodes: {
                'gateway': {
                    status: 'critical',
                    status_label: 'Gateway Offline / RTO',
                    message: 'Router gateway lokal 192.168.1.1 mati, hang, atau antarmukanya tidak merespons paket ICMP/ARP.',
                    ip: '192.168.1.1 (Down)',
                    possible_causes: 'Adaptor daya router mati, firmware crash, atau kabel uplink ke switch terputus.',
                    cli_commands: [
                        { cmd: 'ping -n 4 192.168.1.1', desc: 'Uji apakah ada respons Reply from 192.168.1.1' },
                        { cmd: 'arp -a', desc: 'Periksa apakah MAC Address gateway terbaca di tabel ARP' }
                    ]
                },
                'switch': { status: 'warning', status_label: 'Uplink Port Inactive', message: 'Port menuju router mati.' },
                'client-pc': { status: 'warning', status_label: 'No Internet Access', message: 'Koneksi ke switch aman, namun paket keluar lokal ditolak karena gateway offline.' },
                'laptop': { status: 'warning', status_label: 'No Internet Access', message: 'Tidak dapat keluar segmen lokal.' },
                'smartphone': { status: 'warning', status_label: 'No Internet Access', message: 'WiFi terputus atau no internet.' },
                'dns': { status: 'critical', status_label: 'Unreachable', message: 'Jalur ke resolver publik terputus.' },
                'cloud': { status: 'critical', status_label: 'Unreachable', message: 'Akses keluar terputus di gateway.' }
            }
        },

        'internet_down': {
            title: 'Internet Down (Upstream WAN Disconnected)',
            badgeHtml: '<i class="bi bi-cloud-slash-fill text-danger me-1"></i>Critical: Internet Down (ISP Fiber Link Cut)',
            pills: {
                cable: '<span class="badge bg-success-subtle text-success border border-success-subtle">Connected (LAN OK)</span>',
                dhcp: '<span class="badge bg-success-subtle text-success border border-success-subtle">Valid Lease</span>',
                dns: '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">WAN Disconnected</span>'
            },
            affectedLines: { 'line-router-cloud': 'line-problem', 'line-router-dns': 'line-problem' },
            nodes: {
                'cloud': {
                    status: 'critical',
                    status_label: 'WAN Disconnected',
                    message: 'Jaringan upstream ISP putus (LOS / Loss of Signal pada modem fiber optik).',
                    ip: 'WAN Down',
                    possible_causes: 'Kabel fiber optik putus di jalan, pemeliharaan massal ISP, atau modem optik mati.',
                    cli_commands: [
                        { cmd: 'ping 8.8.8.8', desc: 'Uji konektivitas internet luar (akan menghasilkan RTO)' },
                        { cmd: 'tracert -d 8.8.8.8', desc: 'Lacak lompatan: akan berhenti di router 192.168.1.1' }
                    ]
                },
                'dns': { status: 'critical', status_label: 'Unreachable', message: 'Resolver publik tidak dapat dihubungi dari lokal.' },
                'gateway': { status: 'warning', status_label: 'WAN Interface Down', message: 'Antarmuka WAN merah / LOS, namun jaringan LAN internal tetap aktif.' },
                'switch': { status: 'healthy', status_label: 'Healthy', message: 'Jaringan lokal internal beroperasi penuh.' },
                'client-pc': { status: 'healthy', status_label: 'Healthy (LAN OK)', message: 'Koneksi lokal normal, hanya akses internet WAN terhenti.' },
                'laptop': { status: 'healthy', status_label: 'Healthy (LAN OK)', message: 'Koneksi lokal normal.' },
                'smartphone': { status: 'healthy', status_label: 'Healthy (WiFi OK)', message: 'Tersambung ke WiFi dengan status "No Internet".' }
            }
        }
    };

    // Active scenario & runtime states container
    let activeScenarioKey = 'healthy';
    let currentNodesState = {};

    // 4. Update Node Visual Styling & State
    function renderNodeState(nodeId, nodeState) {
        currentNodesState[nodeId] = nodeState;
        const gNode = document.querySelector(`.topology-node[data-node="${nodeId}"]`);
        if (!gNode) return;

        const statusNorm = normalizeStatus(nodeState.status);
        const theme = STATUS_CONFIG[statusNorm];

        // Update rect fill and stroke
        const rect = gNode.querySelector('rect');
        if (rect) {
            rect.setAttribute('fill', theme.fill);
            rect.setAttribute('stroke', theme.stroke);
            rect.setAttribute('stroke-width', statusNorm === 'critical' ? '2.5' : (statusNorm === 'warning' ? '2.2' : '2'));
        }

        // Update Tooltip Instance dynamically so it NEVER lags or says "Healthy" on troubled nodes!
        const base = TOPOLOGY_NODES[nodeId];
        const statusLabel = nodeState.status_label || theme.label;
        const msgSnippet = nodeState.message ? `<div class="mt-1 small text-start" style="font-size: 0.72rem; max-width: 200px;">${nodeState.message}</div>` : '';
        const tooltipHtml = `<strong>${base.name}</strong><br><span class="small text-muted">${base.defaultRole}</span><br><span class="badge ${theme.badgeClass} mt-1">Status: ${statusLabel}</span>${msgSnippet}`;

        gNode.setAttribute('title', tooltipHtml);
        gNode.setAttribute('data-bs-original-title', tooltipHtml);

        const existingTooltip = bootstrap.Tooltip.getInstance(gNode);
        if (existingTooltip) {
            existingTooltip.setContent({ '.tooltip-inner': tooltipHtml });
        } else {
            new bootstrap.Tooltip(gNode, {
                html: true,
                placement: 'top',
                title: tooltipHtml
            });
        }
    }

    // Reset lines
    function resetTopologyLines() {
        document.querySelectorAll('.topology-line').forEach(line => {
            line.classList.remove('line-highlight', 'line-problem', 'line-warning');
        });
    }

    // 5. Apply Scenario Main Routine
    function applyScenario(scenarioKey) {
        const scenario = SCENARIOS[scenarioKey] || SCENARIOS['healthy'];
        activeScenarioKey = scenarioKey;
        resetTopologyLines();

        // Update Header Badge
        const scenarioBadge = document.getElementById('topologyScenarioBadge');
        if (scenarioBadge) {
            scenarioBadge.innerHTML = scenario.badgeHtml;
        }

        // Update Bottom Status Pills
        const bottomCable = document.getElementById('statusCablePill');
        const bottomDhcp = document.getElementById('statusDhcpPill');
        const bottomDns = document.getElementById('statusDnsPill');

        if (bottomCable && scenario.pills?.cable) bottomCable.innerHTML = scenario.pills.cable;
        if (bottomDhcp && scenario.pills?.dhcp) bottomDhcp.innerHTML = scenario.pills.dhcp;
        if (bottomDns && scenario.pills?.dns) bottomDns.innerHTML = scenario.pills.dns;

        // Apply line styles
        if (scenario.affectedLines) {
            Object.entries(scenario.affectedLines).forEach(([lineId, lineClass]) => {
                const el = document.getElementById(lineId);
                if (el) el.classList.add(lineClass);
            });
        }

        // Apply unified state to all nodes
        Object.keys(TOPOLOGY_NODES).forEach(nodeId => {
            const baseInfo = TOPOLOGY_NODES[nodeId];
            const nodeScenarioData = scenario.nodes[nodeId] || { status: 'healthy', status_label: 'Healthy' };
            const fullNodeState = {
                id: nodeId,
                name: baseInfo.name,
                role: baseInfo.defaultRole,
                layer: baseInfo.layer,
                ip: nodeScenarioData.ip || baseInfo.defaultIp,
                status: nodeScenarioData.status || 'healthy',
                status_label: nodeScenarioData.status_label || 'Healthy',
                message: nodeScenarioData.message || 'Beroperasi normal.',
                possible_causes: nodeScenarioData.possible_causes || null,
                cli_commands: nodeScenarioData.cli_commands || baseInfo.defaultCli
            };
            renderNodeState(nodeId, fullNodeState);
        });
    }

    // 6. Node Click Detail Modal with True Condition
    function openNodeDetailModal(nodeId) {
        const base = TOPOLOGY_NODES[nodeId];
        const state = currentNodesState[nodeId];
        if (!base || !state) return;

        const statusNorm = normalizeStatus(state.status);
        const theme = STATUS_CONFIG[statusNorm];

        // Header info
        document.getElementById('modalNodeIcon').textContent = base.icon;
        document.getElementById('modalNodeName').textContent = base.name;
        document.getElementById('modalNodeLayer').textContent = base.layer;

        // Status Badge
        const statusBadge = document.getElementById('modalNodeStatusBadge');
        if (statusBadge) {
            statusBadge.className = `badge ${theme.badgeClass}`;
            statusBadge.textContent = `Status: ${state.status_label || theme.label}`;
        }

        // Role & Description
        document.getElementById('modalNodeRole').textContent = base.defaultRole;
        document.getElementById('modalNodeDesc').textContent = base.description;

        // Condition / Relationship
        const diagEl = document.getElementById('modalNodeDiag');
        if (diagEl) {
            let detailHtml = `<strong>Kondisi Saat Ini (${activeScenarioKey.toUpperCase()}):</strong><br>`;
            detailHtml += `<span class="${statusNorm === 'critical' ? 'text-danger' : (statusNorm === 'warning' ? 'text-warning-emphasis' : 'text-success')} fw-medium">${state.message}</span>`;
            if (state.ip) {
                detailHtml += `<div class="mt-2 small text-muted font-monospace"><i class="bi bi-hdd-network me-1"></i>IP Address: <strong>${state.ip}</strong></div>`;
            }
            if (state.possible_causes) {
                detailHtml += `<div class="mt-2 small text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i><strong>Kemungkinan Penyebab:</strong> ${state.possible_causes}</div>`;
            }
            diagEl.innerHTML = detailHtml;
        }

        // CLI Commands
        const cmdContainer = document.getElementById('modalNodeCommands');
        if (cmdContainer) {
            const commands = state.cli_commands || base.defaultCli;
            cmdContainer.innerHTML = commands.map(c => `
                <div class="command-line mb-2">
                    <code>${c.cmd}</code>
                    <button type="button" class="copy-btn" data-copy="${c.cmd}" title="Salin Perintah">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="text-muted small mb-2" style="font-size: 0.76rem;">${c.desc}</div>
            `).join('');

            // Bind copy button
            cmdContainer.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const text = this.getAttribute('data-copy');
                    navigator.clipboard.writeText(text).then(() => {
                        const prev = this.innerHTML;
                        this.innerHTML = '<i class="bi bi-check2 text-success"></i>';
                        setTimeout(() => this.innerHTML = prev, 1500);
                    });
                });
            });
        }

        // Show modal
        const modalEl = document.getElementById('topologyNodeModal');
        if (modalEl) {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        }
    }

    // 7. Bind Interactions & Event Listeners
    document.querySelectorAll('.topology-node').forEach(gNode => {
        const nodeId = gNode.getAttribute('data-node');
        if (!TOPOLOGY_NODES[nodeId]) return;

        // Hover In
        gNode.addEventListener('mouseenter', () => {
            const lines = NODE_LINES_MAP[nodeId] || [];
            lines.forEach(lineId => {
                const line = document.getElementById(lineId);
                if (line) line.classList.add('line-highlight');
            });
            gNode.classList.add('node-active');
        });

        // Hover Out
        gNode.addEventListener('mouseleave', () => {
            const lines = NODE_LINES_MAP[nodeId] || [];
            lines.forEach(lineId => {
                const line = document.getElementById(lineId);
                if (line) line.classList.remove('line-highlight');
            });
            gNode.classList.remove('node-active');
        });

        // Click to Open Modal
        gNode.addEventListener('click', (e) => {
            e.preventDefault();
            openNodeDetailModal(nodeId);
        });
    });

    // Scenario Select Listener
    const scenarioSelect = document.getElementById('topologyScenarioSelect');
    scenarioSelect?.addEventListener('change', (e) => {
        applyScenario(e.target.value);
    });

    // Expose modular function for external diagnosis mapping
    window.updateTopologyByDiagnosis = function(categorySlug) {
        if (!categorySlug) {
            applyScenario('healthy');
            return;
        }

        const map = {
            'physical-layer-issue': 'physical',
            'network-layer-issue': 'network',
            'application-layer-issue': 'dns',
            'wireless-issue': 'wireless',
            'performance-issue': 'performance',
            'security-issue': 'security',
            'data-link-layer-issue': 'physical'
        };

        const targetScenario = map[categorySlug] || 'healthy';
        if (scenarioSelect) scenarioSelect.value = targetScenario;
        applyScenario(targetScenario);
    };

    // Initial Load: Apply Healthy Scenario
    applyScenario('healthy');
});
