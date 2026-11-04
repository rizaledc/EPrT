<?php
declare(strict_types=1);

/** Sanitasi SVG sederhana berbasis whitelist (untuk logo sponsor). */
final class SvgSanitizer
{
    private const TAGS = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'text', 'tspan', 'defs', 'lineargradient', 'radialgradient', 'stop', 'title', 'desc', 'clippath', 'mask'];
    private const ATTRS = ['viewbox', 'width', 'height', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'd', 'points', 'fill', 'stroke', 'stroke-width',
        'stroke-linecap', 'stroke-linejoin', 'opacity', 'fill-opacity', 'stroke-opacity', 'transform', 'id', 'class', 'offset', 'stop-color', 'stop-opacity',
        'gradientunits', 'gradienttransform', 'font-family', 'font-size', 'font-weight', 'text-anchor', 'fill-rule', 'clip-rule', 'clip-path', 'mask', 'xmlns', 'version', 'preserveaspectratio'];

    public static function clean(string $svg): ?string
    {
        if (stripos($svg, '<!ENTITY') !== false || stripos($svg, '<!DOCTYPE') !== false) {
            return null;
        }
        $dom = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_use_internal_errors($prev);
        if (!$ok || !$dom->documentElement || strtolower($dom->documentElement->localName) !== 'svg') {
            return null;
        }
        self::walk($dom->documentElement);
        return $dom->saveXML($dom->documentElement);
    }

    private static function walk(DOMElement $el): void
    {
        foreach (iterator_to_array($el->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                if (!in_array(strtolower($child->localName), self::TAGS, true)) {
                    $el->removeChild($child);
                    continue;
                }
                self::walk($child);
            } elseif ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction || $child instanceof DOMCdataSection) {
                $el->removeChild($child);
            }
        }
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $val = strtolower($attr->nodeValue ?? '');
            if (!in_array($name, self::ATTRS, true) || str_contains($val, 'javascript:') || str_contains($val, 'url(http') || str_contains($val, 'expression(')) {
                $el->removeAttribute($attr->nodeName);
            }
        }
    }
}
