// Local PDFKit rasterizer and Core Image QR decoder; no browser or device access.
import Foundation
import AppKit
import PDFKit
import CoreImage
let started = Date()
let dir = URL(fileURLWithPath: CommandLine.arguments[1])
let names = ["tts-a5", "tts-58mm", "nota-a5", "nota-58mm"]
let fixture = try JSONSerialization.jsonObject(with: Data(contentsOf: dir.appendingPathComponent("public-fixture.json"))) as! [String: Any]
let expected = fixture["tracking_url"] as! String
var results = [[String: Any]]()
for name in names {
    let doc = PDFDocument(url: dir.appendingPathComponent(name + ".pdf"))!
    precondition(doc.pageCount == (name == "nota-a5" ? 2 : 1), "Unexpected page count: \(name)")
    var documentDecoded = [String]()
    var documentText = ""
    for pageIndex in 0..<doc.pageCount {
    let imageName = name + "-" + String(pageIndex + 1)
    let page = doc.page(at: pageIndex)!
    let bounds = page.bounds(for: .mediaBox)
    let expectedWidth: CGFloat = name.hasSuffix("a5") ? 148 : 58
    let expectedHeight: CGFloat = name.hasSuffix("a5") ? 210 : (name == "tts-58mm" ? 280 : 400)
    precondition(abs(bounds.width * 25.4 / 72 - expectedWidth) < 0.01)
    precondition(abs(bounds.height * 25.4 / 72 - expectedHeight) < 0.01)
    let scale: CGFloat = 150 / 72
    let width = Int(ceil(bounds.width * scale)), height = Int(ceil(bounds.height * scale))
    let bitmap = NSBitmapImageRep(bitmapDataPlanes: nil, pixelsWide: width, pixelsHigh: height, bitsPerSample: 8, samplesPerPixel: 4, hasAlpha: true, isPlanar: false, colorSpaceName: .deviceRGB, bytesPerRow: 0, bitsPerPixel: 0)!
    let context = NSGraphicsContext(bitmapImageRep: bitmap)!
    NSGraphicsContext.saveGraphicsState()
    NSGraphicsContext.current = context
    NSColor.white.setFill()
    NSRect(x: 0, y: 0, width: width, height: height).fill()
    context.cgContext.scaleBy(x: scale, y: scale)
    page.draw(with: .mediaBox, to: context.cgContext)
    NSGraphicsContext.restoreGraphicsState()
    let png = bitmap.representation(using: .png, properties: [:])!
    try png.write(to: dir.appendingPathComponent(imageName + ".png"))
    // Decode the serialized PDF render, rather than the original SVG.
    let raster = CIImage(data: try Data(contentsOf: dir.appendingPathComponent(imageName + ".png")))!
    let detector = CIDetector(ofType: CIDetectorTypeQRCode, context: nil, options: [CIDetectorAccuracy: CIDetectorAccuracyHigh])!
    let decoded = detector.features(in: raster).compactMap { ($0 as? CIQRCodeFeature)?.messageString }
    documentDecoded += decoded
    let text = page.string!
    precondition(!text.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty, "Blank page")
    documentText += text + "\n"
    for forbidden in ["734567", "734.567", "23456", "23.456", "4567", "4.567", "modal", "INTERNAL_COST_SENTINEL"] {
        precondition(!text.contains(forbidden), "Leaked private value: \(forbidden)")
    }
    var minX = bounds.width, minY = bounds.height, maxX: CGFloat = 0, maxY: CGFloat = 0
    for index in 0..<(text as NSString).length {
        let rect = page.characterBounds(at: index)
        if rect.isEmpty { continue }
        precondition(bounds.insetBy(dx: 2, dy: 2).contains(rect), "Text outside page: \(name) \(rect)")
        minX = min(minX, rect.minX); minY = min(minY, rect.minY)
        maxX = max(maxX, rect.maxX); maxY = max(maxY, rect.maxY)
    }
    try text.write(to: dir.appendingPathComponent(imageName + ".txt"), atomically: true, encoding: .utf8)
    results.append(["document": name, "page": pageIndex + 1, "pages": doc.pageCount, "width_mm": bounds.width * 25.4 / 72, "height_mm": bounds.height * 25.4 / 72, "decoded": decoded, "text_bounds_pt": [minX, minY, maxX, maxY], "render_pixels": [width, height]])
    }
    precondition(documentDecoded == [expected], "QR mismatch for \(name): \(documentDecoded)")
    precondition(documentText.contains("CONTOH SINTETIS T006"))
}
let report: [String: Any] = ["renderer": "macOS PDFKit at 150 dpi", "decoder": "macOS Core Image CIDetectorTypeQRCode", "duration_seconds": Date().timeIntervalSince(started), "results": results]
let data = try JSONSerialization.data(withJSONObject: report, options: [.prettyPrinted, .sortedKeys, .withoutEscapingSlashes])
try data.write(to: dir.appendingPathComponent("render-check.json"))
print(String(data: data, encoding: .utf8)!)
