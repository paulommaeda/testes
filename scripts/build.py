"""Monta exclusivamente a pasta instalável do plugin e o manifesto de atualização."""
import json
import re
import zipfile
from pathlib import Path

ROOT=Path(__file__).resolve().parents[1]
PLUGIN=ROOT/'maeda-perfis'
source=(PLUGIN/'maeda-perfis.php').read_text()
def header(name):
    found=re.search(r'^ \* '+re.escape(name)+r':\s*(.+)$',source,re.M)
    if not found: raise SystemExit('Cabeçalho ausente: '+name)
    return found.group(1).strip()
version=header('Version')
if not re.fullmatch(r'\d+\.\d+\.\d+',version): raise SystemExit('Versão inválida')
output=ROOT/'dist'
output.mkdir(exist_ok=True)
filename=output/f'maeda-perfis-{version}.zip'
with zipfile.ZipFile(filename,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9) as archive:
    for path in sorted(PLUGIN.rglob('*')):
        if path.is_file() and not any(part.startswith('.') for part in path.relative_to(PLUGIN).parts):
            archive.write(path,Path('maeda-perfis')/path.relative_to(PLUGIN))
    archive.write(ROOT/'README.md','maeda-perfis/README.md')
    archive.write(ROOT/'CHANGELOG.md','maeda-perfis/CHANGELOG.md')
    if (ROOT/'TESTING.md').exists(): archive.write(ROOT/'TESTING.md','maeda-perfis/TESTING.md')
    if (ROOT/'LICENSE').exists(): archive.write(ROOT/'LICENSE','maeda-perfis/LICENSE')
manifest={'version':version,'requires':header('Requires at least'),'requires_php':header('Requires PHP'),'tested':'7.1.3','package':f'https://github.com/paulommaeda/testes/releases/download/v{version}/{filename.name}'}
(output/'update.json').write_text(json.dumps(manifest,ensure_ascii=False,indent=2)+'\n')
with zipfile.ZipFile(filename) as archive:
    if archive.testzip(): raise SystemExit('Falha na integridade do ZIP')
    names=archive.namelist()
    assert 'maeda-perfis/maeda-perfis.php' in names
    assert not any('/tests/' in name or 'node_modules' in name for name in names)
print(filename)
print(f'{len(names)} arquivos; {filename.stat().st_size} bytes')
