import Foundation
import CoreImage
let data = try Data(contentsOf: URL(fileURLWithPath: CommandLine.arguments[1]))
let cases = try JSONSerialization.jsonObject(with:data) as! [[String:String]]
let detector=CIDetector(ofType:CIDetectorTypeQRCode, context:nil, options:[CIDetectorAccuracy:CIDetectorAccuracyHigh])!
for row in cases {
 let source=CIImage(contentsOf:URL(fileURLWithPath:row["image"]!))!
 let found=detector.features(in:source).compactMap{($0 as? CIQRCodeFeature)?.messageString}
 precondition(found == [row["expected"]!], "QR mismatch for " + row["case"]!)
 print(row["case"]! + " QR exact app URL + 64-character model token PASS")
}
