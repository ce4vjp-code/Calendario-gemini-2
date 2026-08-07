package cl.liceotpggm.calendario

import android.annotation.SuppressLint
import android.os.Bundle
import android.webkit.WebChromeClient
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.appcompat.app.AppCompatActivity

class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Creamos el WebView de forma programática
        webView = WebView(this)
        setContentView(webView)

        // Configuramos el WebView para que se comporte como un navegador moderno
        val webSettings: WebSettings = webView.settings
        webSettings.javaScriptEnabled = true
        webSettings.domStorageEnabled = true
        webSettings.loadWithOverviewMode = true
        webSettings.useWideViewPort = true
        webSettings.builtInZoomControls = false
        webSettings.displayZoomControls = false
        
        // Evitar que los enlaces abran en Chrome externo
        webView.webViewClient = WebViewClient()
        webView.webChromeClient = WebChromeClient()

        // REEMPLAZAR ESTA URL POR LA URL PÚBLICA DEL COLEGIO
        // Ej: https://new.liceotpggm.cl/calendario/
        val urlPublica = "https://new.liceotpggm.cl/evaluaciones"
        
        webView.loadUrl(urlPublica)
    }

    // Permitir volver atrás en el historial web con el botón de retroceso del celular
    override fun onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack()
        } else {
            super.onBackPressed()
        }
    }
}
