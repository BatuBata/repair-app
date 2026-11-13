import Foundation
import CoreImage
let token=try String(contentsOfFile:"/private/tmp/t017-a3-fpm-final/token",encoding:.utf8)
let expected="https://repair.example/t/"+token
let source=CIImage(contentsOf:URL(fileURLWithPath:"/private/tmp/t017-a3-fpm-final/long-thermal-qr-5.png"))!
let detector=CIDetector(ofType:CIDetectorTypeQRCode,context:nil,options:[CIDetectorAccuracy:CIDetectorAccuracyHigh])!
let found=detector.features(in:source).compactMap{($0 as? CIQRCodeFeature)?.messageString}
precondition(found == [expected],"QR mismatch")
print("long thermal page 5 QR exact URL + model token 64 PASS")
