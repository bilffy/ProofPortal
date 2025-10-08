from PIL import Image
import sys
import os
import io
import base64

def resize_image_to_base64(input_path, resize_to):
    with Image.open(input_path) as img:
        width, height = img.size

        print(f"resize_to value is {resize_to}.", file=sys.stderr)
        print(f"Image Dimensions width: {width}, height: {height}.", file=sys.stderr)

        # Determine orientation and whether to skip
        if height > width:
            # Portrait
            if height <= resize_to:
                resized_img = img.copy()
            else:
                new_height = resize_to
                new_width = int((resize_to / height) * width)
                resized_img = img.resize((new_width, new_height), Image.LANCZOS)
        else:
            # Landscape
            if width <= resize_to:
                resized_img = img.copy()
            else:
                new_width = resize_to
                new_height = int((resize_to / width) * height)
                resized_img = img.resize((new_width, new_height), Image.LANCZOS)

        # Convert image to base64
        buffered = io.BytesIO()
        resized_img.save(buffered, format=img.format or "JPEG")
        img_bytes = buffered.getvalue()
        img_base64 = base64.b64encode(img_bytes).decode("utf-8")

        return img_base64


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python resizer.py <input_image_path> <resize_to>", file=sys.stderr)
        sys.exit(1)

    input_path = sys.argv[1]

    try:
        resize_to = int(sys.argv[2])
    except ValueError:
        print("resize_to must be an integer.", file=sys.stderr)
        sys.exit(1)

    if not os.path.exists(input_path):
        print(f"File not found: {input_path}", file=sys.stderr)
        sys.exit(1)

    base64_string = resize_image_to_base64(input_path, resize_to)

    # Output base64 to stdout
    print(base64_string)