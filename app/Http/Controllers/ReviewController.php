<?php

namespace App\Http\Controllers;

use App\Models\BidWinner;
use App\Models\Review;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReviewController extends Controller
{
    /**
     * Display the public review details page.
     */
    public function show(Review $review): InertiaResponse
    {
        if ($review->moderation_status !== 'approved') {
            abort(404, 'Review not found');
        }

        $review->load(['bid:id,name,image,url,price', 'user:msisdn,name']);

        $isWinner = false;
        if ($review->bidid) {
            $isWinner = BidWinner::where('msisdn', $review->user_id)
                ->where('bidid', $review->bidid)
                ->exists();
        }

        return Inertia::render('ReviewShow', [
            'review' => $review,
            'isWinner' => $isWinner,
            'cardImageUrl' => route('reviews.image', $review),
            'shareUrl' => route('reviews.show', $review),
        ]);
    }

    /**
     * Generate and stream a dynamic PNG sharing card for the review.
     */
    public function image(Review $review): StreamedResponse
    {
        if ($review->moderation_status !== 'approved') {
            abort(404, 'Review not found');
        }

        $review->load('bid');

        $isWinner = false;
        if ($review->bidid) {
            $isWinner = BidWinner::where('msisdn', $review->user_id)
                ->where('bidid', $review->bidid)
                ->exists();
        }

        // Define Font Paths
        $fontPath = resource_path('fonts/Inter-Regular.ttf');
        $boldFontPath = resource_path('fonts/Inter-Bold.ttf');

        // Dynamically ensure fonts are present
        $this->ensureFontsExist($fontPath, $boldFontPath);

        // Canvas Setup (1200 x 630 landscape card ratio)
        $im = imagecreatetruecolor(1200, 630);

        // Core Palette & Drawing Tokens
        $navy = imagecolorallocate($im, 13, 27, 42);       // #0d1b2a
        $lemon = imagecolorallocate($im, 200, 224, 0);     // #c8e000
        $white = imagecolorallocate($im, 255, 255, 255);
        $mint = imagecolorallocate($im, 192, 224, 222);     // #c0e0de
        $darkText = imagecolorallocate($im, 22, 37, 33);   // #162521
        $gray = imagecolorallocate($im, 120, 120, 120);
        $gold = imagecolorallocate($im, 245, 230, 66);     // #f5e642
        $starEmptyColor = imagecolorallocate($im, 40, 55, 70); // Darker blue/gray for empty stars

        // Fill background
        imagefill($im, 0, 0, $navy);

        // Draw lemon accent border
        imagesetthickness($im, 6);
        imagerectangle($im, 12, 12, 1188, 618, $lemon);

        // Let's use font paths if they were downloaded successfully and are valid, or fall back to native system paths / default fonts
        $hasTTF = file_exists($fontPath) && @filesize($fontPath) > 1024
            && file_exists($boldFontPath) && @filesize($boldFontPath) > 1024;

        if (! $hasTTF) {
            // Search for local system fonts to allow proper TTF rendering and custom font sizes
            $fallbackRegularFonts = [
                '/System/Library/Fonts/Supplemental/Arial.ttf',
                '/Library/Fonts/Arial.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
                '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
                'C:\\Windows\\Fonts\\arial.ttf',
            ];
            $fallbackBoldFonts = [
                '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
                '/Library/Fonts/Arial Bold.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
                '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
                'C:\\Windows\\Fonts\\arialbd.ttf',
            ];

            foreach ($fallbackRegularFonts as $f) {
                if (file_exists($f)) {
                    $fontPath = $f;
                    break;
                }
            }
            foreach ($fallbackBoldFonts as $fb) {
                if (file_exists($fb)) {
                    $boldFontPath = $fb;
                    break;
                }
            }

            $hasTTF = file_exists($fontPath) && file_exists($boldFontPath);
        }

        if ($hasTTF) {
            // Draw Branding Logo (Top Left)
            imagettftext($im, 38, 0, 60, 90, $white, $boldFontPath, 'CarryGo');

            // Draw Pill Container (VERIFIED WINNER vs VERIFIED USER)
            $pillText = $isWinner ? 'VERIFIED WINNER' : 'VERIFIED USER';
            $pillColor = $isWinner ? $lemon : $mint;
            $pillTextCol = $navy;

            $this->imagefilledroundedrect($im, 290, 50, 510, 90, 10, $pillColor);
            imagettftext($im, 11, 0, 312, 75, $pillTextCol, $boldFontPath, $pillText);

            // Draw Masked Phone
            $digits = preg_replace('/\D/', '', $review->user_id);
            $maskedPhone = '+'.substr($digits, 0, 3).' '.substr($digits, 3, 3).' *** '.substr($digits, -4);
            imagettftext($im, 22, 0, 60, 170, $white, $boldFontPath, $maskedPhone);

            // Draw Vector Rating Stars (y = 220)
            for ($i = 0; $i < 5; $i++) {
                $starColor = ($i < $review->rating) ? $gold : $starEmptyColor;
                $this->drawStar($im, 75 + ($i * 45), 220, 5, 16, 7, $starColor);
            }

            // Draw Comment (wrap based on split layout)
            $comment = '"'.$review->comment.'"';
            $maxWidth = $review->bid ? 620 : 1080;
            $fontSize = 26;
            $wrappedLines = $this->wrapText($fontSize, 0, $fontPath, $comment, $maxWidth);

            // Limit to 5 lines maximum and append ellipsis if it exceeds
            $maxLines = 5;
            if (count($wrappedLines) > $maxLines) {
                $wrappedLines = array_slice($wrappedLines, 0, $maxLines);
                $wrappedLines[$maxLines - 1] .= '...';
            }

            $yOffset = 300;
            foreach ($wrappedLines as $line) {
                imagettftext($im, $fontSize, 0, 60, $yOffset, $mint, $fontPath, $line);
                $yOffset += 48;
            }

            // Layout split rendering
            if ($review->bid) {
                // Draw product card background
                $this->imagefilledroundedrect($im, 760, 60, 1140, 570, 24, $white);

                // Draw product image inside card (try/catch protected)
                if ($review->bid->image) {
                    $this->drawProductImage($im, $review->bid->image, 790, 90, 320, 280);
                }

                // Draw product title
                $productName = $review->bid->name;
                $nameLines = $this->wrapText(16, 0, $boldFontPath, $productName, 320);
                $yName = 430;
                foreach (array_slice($nameLines, 0, 2) as $line) {
                    imagettftext($im, 16, 0, 790, $yName, $darkText, $boldFontPath, $line);
                    $yName += 28;
                }

                // Draw Won/Item Price Tag Badge
                $pillGreen = imagecolorallocate($im, 232, 245, 224);
                $forestText = imagecolorallocate($im, 26, 92, 42);
                $this->imagefilledroundedrect($im, 790, 500, 1110, 545, 12, $pillGreen);
                $priceLabel = $isWinner ? 'Won Value: ₦'.$review->bid->price : 'Item Value: ₦'.$review->bid->price;
                imagettftext($im, 13, 0, 810, 529, $forestText, $boldFontPath, $priceLabel);
            } else {
                // General review graphic background: giant quote symbol
                $quoteColor = imagecolorallocate($im, 25, 45, 65);
                imagettftext($im, 160, 0, 960, 230, $quoteColor, $boldFontPath, '“');
            }

            // Draw branding link in the footer
            imagettftext($im, 12, 0, 60, 570, $gray, $fontPath, 'www.carrygo.test');

        } else {
            // GD built-in fonts backup (pure fallback case if download fails completely)
            imagestring($im, 5, 60, 60, 'CarryGo', $white);

            // Draw Pill Container (VERIFIED WINNER vs VERIFIED USER)
            $pillText = $isWinner ? 'VERIFIED WINNER' : 'VERIFIED USER';
            $pillColor = $isWinner ? $lemon : $mint;
            $this->imagefilledroundedrect($im, 180, 52, 340, 80, 5, $pillColor);
            imagestring($im, 2, 195, 59, $pillText, $navy);

            // Draw Masked Phone
            $digits = preg_replace('/\D/', '', $review->user_id);
            $maskedPhone = '+'.substr($digits, 0, 3).' '.substr($digits, 3, 3).' *** '.substr($digits, -4);
            imagestring($im, 4, 60, 110, $maskedPhone, $white);

            // Draw Rating Stars
            imagestring($im, 4, 60, 140, 'Rating: '.str_repeat('* ', $review->rating), $gold);

            // Draw Comment (wrapped based on split layout)
            $comment = '"'.$review->comment.'"';
            $charLimit = $review->bid ? 40 : 80;
            $wrappedComment = wordwrap($comment, $charLimit, "\n");
            $commentLines = explode("\n", $wrappedComment);
            $yOffset = 190;
            foreach (array_slice($commentLines, 0, 5) as $line) {
                imagestring($im, 5, 60, $yOffset, $line, $mint);
                $yOffset += 30;
            }

            // Draw product card on the right if bid is present
            if ($review->bid) {
                // Draw product card background
                $this->imagefilledroundedrect($im, 760, 60, 1140, 570, 24, $white);

                // Draw product image inside card (try/catch protected)
                if ($review->bid->image) {
                    $this->drawProductImage($im, $review->bid->image, 790, 90, 320, 280);
                }

                // Draw product title
                $productName = $review->bid->name;
                $wrappedName = wordwrap($productName, 30, "\n");
                $nameLines = explode("\n", $wrappedName);
                $yName = 430;
                foreach (array_slice($nameLines, 0, 2) as $line) {
                    imagestring($im, 4, 790, $yName, $line, $darkText);
                    $yName += 20;
                }

                // Draw Won/Item Price Tag Badge
                $pillGreen = imagecolorallocate($im, 232, 245, 224);
                $forestText = imagecolorallocate($im, 26, 92, 42);
                $this->imagefilledroundedrect($im, 790, 500, 1110, 545, 12, $pillGreen);
                $priceLabel = $isWinner ? 'Won Value: ₦'.$review->bid->price : 'Item Value: ₦'.$review->bid->price;
                imagestring($im, 3, 810, 515, $priceLabel, $forestText);
            }

            imagestring($im, 2, 60, 570, 'www.carrygo.test', $gray);
        }

        // Return Streamed PNG
        return response()->stream(function () use ($im) {
            imagepng($im);
            imagedestroy($im);
        }, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400, must-revalidate',
        ]);
    }

    /**
     * Draw a mathematically perfect filled star polygon.
     */
    private function drawStar($image, int $cx, int $cy, int $spikes, int $outerRadius, int $innerRadius, int $color): void
    {
        $points = [];
        $angle = pi() / $spikes;

        for ($i = 0; $i < 2 * $spikes; $i++) {
            $currentAngle = ($i * $angle) - (pi() / 2);
            $r = ($i % 2 === 0) ? $outerRadius : $innerRadius;
            $points[] = (int) ($cx + cos($currentAngle) * $r);
            $points[] = (int) ($cy + sin($currentAngle) * $r);
        }

        imagefilledpolygon($image, $points, $color);
    }

    /**
     * Bounding box measurement word wrapper.
     */
    private function wrapText(float $fontSize, float $angle, string $fontFile, string $text, float $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine.' '.$word;
            $box = imagettfbbox($fontSize, $angle, $fontFile, $testLine);
            $width = $box[2] - $box[0];

            if ($width > $maxWidth) {
                if ($currentLine !== '') {
                    $lines[] = $currentLine;
                    $currentLine = $word;
                } else {
                    $lines[] = $word;
                    $currentLine = '';
                }
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    /**
     * Rounded rectangle polygon fill.
     */
    private function imagefilledroundedrect($im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    /**
     * Composites external image onto the GD canvas safely while preserving aspect ratio.
     */
    private function drawProductImage($im, string $url, int $x, int $y, int $w, int $h): void
    {
        try {
            $imgData = @file_get_contents($url);
            if ($imgData) {
                $srcImg = @imagecreatefromstring($imgData);
                if ($srcImg) {
                    $srcW = imagesx($srcImg);
                    $srcH = imagesy($srcImg);

                    $ratio = min($w / $srcW, $h / $srcH);
                    $newW = (int) ($srcW * $ratio);
                    $newH = (int) ($srcH * $ratio);
                    $destX = (int) ($x + ($w - $newW) / 2);
                    $destY = (int) ($y + ($h - $newH) / 2);

                    imagecopyresampled($im, $srcImg, $destX, $destY, 0, 0, $newW, $newH, $srcW, $srcH);
                    imagedestroy($srcImg);
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }

    /**
     * Helper to download raw Inter TTF font files if missing.
     */
    private function ensureFontsExist(string $fontPath, string $boldFontPath): void
    {
        try {
            if (! file_exists($fontPath)) {
                if (! is_dir(dirname($fontPath))) {
                    mkdir(dirname($fontPath), 0755, true);
                }
                $content = @file_get_contents('https://github.com/google/fonts/raw/main/ofl/inter/static/Inter-Regular.ttf');
                if ($content) {
                    file_put_contents($fontPath, $content);
                }
            }
            if (! file_exists($boldFontPath)) {
                if (! is_dir(dirname($boldFontPath))) {
                    mkdir(dirname($boldFontPath), 0755, true);
                }
                $content = @file_get_contents('https://github.com/google/fonts/raw/main/ofl/inter/static/Inter-Bold.ttf');
                if ($content) {
                    file_put_contents($boldFontPath, $content);
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback to built-in fonts
        }
    }
}
