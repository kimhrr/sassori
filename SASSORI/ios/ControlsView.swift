import SwiftUI

struct DeviceState: Codable {
    var light: String
    var aircond: String
    var curtain: String
}

struct ControlsView: View {
    @State private var state = DeviceState(light: "off", aircond: "off", curtain: "stop")
    let deviceId = "DB01"
    let base = URL(string: "http://your-server/api")!

    var body: some View {
        VStack(spacing: 16) {
            Text("Smart Controls").font(.title).bold()
            Toggle("Light", isOn: Binding(
                get: { state.light == "on" },
                set: { v in state.light = v ? "on" : "off"; push() }
            ))
            .padding()

            Toggle("Aircond", isOn: Binding(
                get: { state.aircond == "on" },
                set: { v in state.aircond = v ? "on" : "off"; push() }
            ))
            .padding()

            HStack {
                Button("Curtain Up") { state.curtain = "up"; push() }
                Button("Stop") { state.curtain = "stop"; push() }
                Button("Down") { state.curtain = "down"; push() }
            }
        }
        .onAppear(perform: pull)
        .padding()
    }

    func pull() {
        let url = base.appendingPathComponent("commands").appending("device_id", value: deviceId)
        URLSession.shared.dataTask(with: url) { data,_,_ in
            if let data = data, let s = try? JSONDecoder().decode(DeviceState.self, from: data) {
                DispatchQueue.main.async { self.state = s }
            }
        }.resume()
    }

    func push() {
        var req = URLRequest(url: base.appendingPathComponent("commands"))
        req.httpMethod = "POST"
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        let body: [String:Any] = ["device_id":deviceId, "light":state.light, "aircond":state.aircond, "curtain":state.curtain]
        req.httpBody = try? JSONSerialization.data(withJSONObject: body)
        URLSession.shared.dataTask(with: req).resume()
    }
}

private extension URL {
    func appending(_ queryItem: String, value: String) -> URL {
        var comp = URLComponents(url: self, resolvingAgainstBaseURL: false)!
        var items = comp.queryItems ?? []
        items.append(URLQueryItem(name: queryItem, value: value))
        comp.queryItems = items
        return comp.url!
    }
}
