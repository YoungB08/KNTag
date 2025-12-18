import qrcode
from qrcode.constants import ERROR_CORRECT_H
import time

data = "https://www.facebook.com/it.knz"

qr = qrcode.QRCode(
    version=None,
    error_correction=ERROR_CORRECT_H,
    box_size=10,
    border=2,
)

while True:
    username = input("Nhập username:")
    url = data + "/" + username
    print(url)
    qr.add_data(url)
    qr.make(fit=True)   

    img = qr.make_image(fill_color="black", back_color="white")

    path = "F:/KNTag/qr/qr-" + username + ".png"   # LƯU Ở ROOT Ổ F
    img.save(path)

    print("✅ Đã tạo QR tại:", path)
    qr.clear()
