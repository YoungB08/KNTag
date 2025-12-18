import os
import argparse
from PIL import Image

CM_TO_INCH = 1 / 2.54
SUPPORTED_EXT = (".jpg", ".jpeg", ".png", ".webp")

def cm_to_px(cm: float, dpi: int) -> int:
    return int(round(cm * CM_TO_INCH * dpi))

def resize_stretch(in_path: str, out_path: str, dpi: int) -> None:
    # 5.7 x 9.1 cm (landscape) => W=9.1, H=5.7
    target_w = cm_to_px(9.1, dpi)
    target_h = cm_to_px(5.7, dpi)

    img = Image.open(in_path)

    # Giữ chế độ an toàn khi lưu JPG
    if img.mode not in ("RGB", "RGBA"):
        img = img.convert("RGB")

    # RESIZE (KÉO GIÃN) - KHÔNG CẮT, KHÔNG VIỀN, KHÔNG GIỮ TỈ LỆ
    out = img.resize((target_w, target_h), Image.Resampling.LANCZOS)

    # Tạo folder output nếu chưa có
    os.makedirs(os.path.dirname(out_path), exist_ok=True)

    # JPG không hỗ trợ alpha
    if out_path.lower().endswith((".jpg", ".jpeg")) and out.mode == "RGBA":
        out = out.convert("RGB")

    out.save(out_path, dpi=(dpi, dpi))

def main():
    ap = argparse.ArgumentParser(description="Batch resize (stretch) images to 9.1x5.7cm landscape (no crop).")
    ap.add_argument("folder", help="Input folder")
    ap.add_argument("--dpi", type=int, default=300, help="DPI (default: 300)")
    ap.add_argument("--suffix", default="", help="Output filename suffix (default: _91x57)")
    args = ap.parse_args()

    input_dir = args.folder
    output_dir = os.path.join(input_dir, "output")
    os.makedirs(output_dir, exist_ok=True)

    files = [f for f in os.listdir(input_dir) if f.lower().endswith(SUPPORTED_EXT)]
    if not files:
        print("Không có ảnh hợp lệ trong folder.")
        return

    for f in files:
        in_path = os.path.join(input_dir, f)
        name, ext = os.path.splitext(f)
        out_path = os.path.join(output_dir, f"{name}{args.suffix}{ext}")
        resize_stretch(in_path, out_path, args.dpi)
        print(f"OK: {f} -> output/{name}{args.suffix}{ext}")

    w = cm_to_px(9.1, args.dpi)
    h = cm_to_px(5.7, args.dpi)
    print(f"\nDONE: {len(files)} files | {w}x{h}px @ {args.dpi} DPI (9.1x5.7cm landscape)")

if __name__ == "__main__":
    main()
