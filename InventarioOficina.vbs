' ===========================================================================
'  Abre Inventario Oficina: enciende todo sin ventanas negras y muestra el
'  sistema como una ventana de programa. Es lo que ejecuta el acceso directo
'  "Inventario Oficina" del escritorio.
' ===========================================================================
Option Explicit
Dim sh, fso, carpeta, rc, url, msg

Set sh = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
carpeta = fso.GetParentFolderName(WScript.ScriptFullName)
sh.CurrentDirectory = carpeta
url = "http://127.0.0.1:8010/"

rc = sh.Run("cmd /c """"" & carpeta & "\windows\arrancar.bat""""", 0, True)

Select Case rc
    Case 0
        msg = ""
    Case 10
        msg = "No se encontro XAMPP en C:\xampp." & vbCrLf & vbCrLf & "Instale XAMPP en esa carpeta y vuelva a intentarlo."
    Case 11
        msg = "El programa todavia no esta instalado." & vbCrLf & vbCrLf & "Haga doble clic en instalar.bat dentro de la carpeta:" & vbCrLf & carpeta
    Case 20
        msg = "No se pudo encender la base de datos (MySQL)." & vbCrLf & vbCrLf & "Reinicie el computador e intente de nuevo. Si el problema sigue, abra el XAMPP Control Panel y pulse Start en MySQL para ver el error."
    Case 30
        msg = "No se pudo encender el sistema." & vbCrLf & vbCrLf & "Puede que otro programa este usando el puerto 8010. Reinicie el computador e intente de nuevo."
    Case Else
        msg = "No se pudo abrir Inventario Oficina (codigo " & rc & ")."
End Select

If msg <> "" Then
    MsgBox msg, vbExclamation, "Inventario Oficina"
    WScript.Quit 1
End If

' Se abre como ventana de programa (sin barra de direcciones) en Edge o Chrome;
' si ninguno funciona, en el navegador predeterminado.
On Error Resume Next
sh.Run "msedge --app=" & url & " --start-maximized", 1, False
If Err.Number <> 0 Then
    Err.Clear
    sh.Run "chrome --app=" & url & " --start-maximized", 1, False
End If
If Err.Number <> 0 Then
    Err.Clear
    sh.Run url, 1, False
End If
