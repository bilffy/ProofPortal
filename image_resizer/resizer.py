from PIL import Image
import sys
import os

def resize_image(input_path, output_path, resize_to):
    with Image.open(input_path) as img:
        width, height = img.size
        
        print(f"resize_to value is {resize_to}.")
        print(f"Image Dimensions width: {width}, height: {height}.")
        
        # Determine orientation
        if height > width:
            # Portrait
            if height <= resize_to:
                print(f"Portrait image is already <= {resize_to}px, skipping resize.")
                img.save(output_path)
                return

            new_height = resize_to
            new_width = int((resize_to / height) * width)

        else:
            # Landscape
            if width <= resize_to:
                print(f"Landscape image is already <= {resize_to}px, skipping resize.")
                img.save(output_path)
                return

            new_width = resize_to
            new_height = int((resize_to / width) * height)

        resized_img = img.resize((new_width, new_height), Image.LANCZOS)
        resized_img.save(output_path)
        print(f"Image resized to {new_width}x{new_height} and saved to {output_path}")


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python resizer.py <input_image_path> <resize_to>")
        sys.exit(1)

    input_path = sys.argv[1]

    try:
        resize_to = int(sys.argv[2])
    except ValueError:
        print("resize_to must be an integer.")
        sys.exit(1)

    if not os.path.exists(input_path):
        print(f"File not found: {input_path}")
        sys.exit(1)

    # Output path = input_resized.jpg
    base, ext = os.path.splitext(input_path)
    output_path = f"{base}_resized{ext}"

    resize_image(input_path, output_path, resize_to)