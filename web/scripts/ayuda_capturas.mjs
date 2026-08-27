import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const base = process.env.AYUDA_BASE_URL || 'http://127.0.0.1:8080';
const user = process.env.AYUDA_USER || 'admin';
const pass = process.env.AYUDA_PASS || 'admin123';
const outDir = path.join(__dirname, '..', 'public', 'assets', 'ayuda');
const shots = [
  { file: 'inicio-panel.png', url: '/index.php', fullPage: true },
  { file: 'barra-superior.png', url: '/index.php', clip: { x: 280, y: 0, width: 900, height: 112 } },
  { file: 'pacientes-listado.png', url: '/pacientes.php' },
  { file: 'agenda-diaria.png', url: '/agenda.php' },
  { file: 'control-diario.png', url: '/control_administrativo.php' },
  { file: 'caja-movimientos.png', url: '/caja.php' },
  { file: 'caja-cierre.png', url: '/caja_cierre.php' },
  { file: 'sistema-config.png', url: '/sistema.php' },
];

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1366, height: 900 } });
  await page.goto(base + '/login.php', { waitUntil: 'networkidle' });
  await page.fill('input[name="usuario"]', user);
  await page.fill('input[name="clave"]', pass);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  if (page.url().includes('login.php')) {
    const err = await page.locator('.alert-error').textContent().catch(() => '');
    throw new Error('Login falló. Revise AYUDA_USER / AYUDA_PASS. ' + (err || '').trim());
  }

  for (const shot of shots) {
    await page.goto(base + shot.url, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    const target = path.join(outDir, shot.file);
    if (shot.clip) {
      await page.screenshot({ path: target, clip: shot.clip });
    } else {
      await page.screenshot({ path: target, fullPage: !!shot.fullPage });
    }
    console.log('OK', shot.file);
  }

  await browser.close();
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
