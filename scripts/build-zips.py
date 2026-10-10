"""打包上傳用的壓縮檔：dist/domingo.zip（主題）、dist/domingo-core.zip（外掛）。

用法：python3 scripts/build-zips.py
"""
import os
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, "dist")
os.makedirs(OUT, exist_ok=True)

for base, name in [("themes", "domingo"), ("plugins", "domingo-core")]:
    src = os.path.join(ROOT, base)
    path = os.path.join(OUT, f"{name}.zip")
    with zipfile.ZipFile(path, "w", zipfile.ZIP_DEFLATED) as z:
        for root, dirs, files in os.walk(os.path.join(src, name)):
            dirs[:] = [d for d in dirs if not d.startswith(".")]
            for f in files:
                if not f.startswith("."):
                    p = os.path.join(root, f)
                    z.write(p, os.path.relpath(p, src))
    print(path)
