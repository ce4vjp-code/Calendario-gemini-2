package cl.liceotpggm.profesor.api

import android.content.Context
import android.content.SharedPreferences
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl
import java.util.concurrent.ConcurrentHashMap

class PersistentCookieJar(context: Context) : CookieJar {
    private val prefs: SharedPreferences = context.getSharedPreferences("CookiePrefs", Context.MODE_PRIVATE)
    private val gson = Gson()
    private val cookieStore = ConcurrentHashMap<String, MutableList<Cookie>>()

    init {
        loadCookiesFromPrefs()
    }

    override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
        val host = url.host
        val currentCookies = cookieStore[host] ?: mutableListOf()
        
        cookies.forEach { newCookie ->
            currentCookies.removeAll { it.name == newCookie.name }
            currentCookies.add(newCookie)
        }
        
        cookieStore[host] = currentCookies
        saveCookiesToPrefs()
    }

    override fun loadForRequest(url: HttpUrl): List<Cookie> {
        val host = url.host
        val cookies = cookieStore[host] ?: mutableListOf()
        val validCookies = mutableListOf<Cookie>()
        val currentTime = System.currentTimeMillis()

        val iterator = cookies.iterator()
        while (iterator.hasNext()) {
            val cookie = iterator.next()
            if (cookie.expiresAt < currentTime) {
                iterator.remove()
            } else {
                validCookies.add(cookie)
            }
        }
        
        return validCookies
    }
    
    private fun saveCookiesToPrefs() {
        // Convert to a simple structure that Gson can serialize easily since Cookie isn't directly serializable by Gson without custom adapters
        val mapToSave = mutableMapOf<String, List<SerializableCookie>>()
        cookieStore.forEach { (host, cookies) ->
            mapToSave[host] = cookies.map { SerializableCookie(it) }
        }
        prefs.edit().putString("cookies", gson.toJson(mapToSave)).apply()
    }

    private fun loadCookiesFromPrefs() {
        val cookiesJson = prefs.getString("cookies", null) ?: return
        try {
            val type = object : TypeToken<Map<String, List<SerializableCookie>>>() {}.type
            val loadedMap: Map<String, List<SerializableCookie>> = gson.fromJson(cookiesJson, type)
            loadedMap.forEach { (host, serializableCookies) ->
                cookieStore[host] = serializableCookies.mapNotNull { it.toCookie() }.toMutableList()
            }
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    fun clear() {
        cookieStore.clear()
        prefs.edit().clear().apply()
    }
    
    // Helper to serialize OkHttp Cookies
    data class SerializableCookie(
        val name: String,
        val value: String,
        val expiresAt: Long,
        val domain: String,
        val path: String,
        val secure: Boolean,
        val httpOnly: Boolean,
        val hostOnly: Boolean
    ) {
        constructor(cookie: Cookie) : this(
            cookie.name, cookie.value, cookie.expiresAt, cookie.domain, 
            cookie.path, cookie.secure, cookie.httpOnly, cookie.hostOnly
        )

        fun toCookie(): Cookie? {
            val builder = Cookie.Builder()
                .name(name)
                .value(value)
                .expiresAt(expiresAt)
                .path(path)
            
            if (secure) builder.secure()
            if (httpOnly) builder.httpOnly()
            if (hostOnly) builder.hostOnlyDomain(domain) else builder.domain(domain)
            
            return builder.build()
        }
    }
}
